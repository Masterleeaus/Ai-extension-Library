<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Domain;

use App\Domains\WorkCore\System\Modules\Finance\Pricing\DTO\PricingDecision;
use InvalidArgumentException;

final class DynamicPriceCalculator
{
    /** @param array<string,mixed> $input */
    public function calculate(array $input): PricingDecision
    {
        $basePrice = $this->integer($input['base_price_minor'] ?? null, 'base_price_minor', 0);
        $currency = strtoupper(trim((string) ($input['currency'] ?? 'AUD')));
        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException('currency must be a three-letter ISO code.');
        }

        $price = $basePrice;
        $context = is_array($input['context'] ?? null) ? $input['context'] : [];
        $rules = is_array($input['rules'] ?? null) ? array_values($input['rules']) : [];
        usort($rules, static function (mixed $left, mixed $right): int {
            $a = is_array($left) ? $left : [];
            $b = is_array($right) ? $right : [];
            return [(int) ($a['priority'] ?? 100), (string) ($a['id'] ?? '')]
                <=> [(int) ($b['priority'] ?? 100), (string) ($b['id'] ?? '')];
        });

        $appliedRuleIds = [];
        $ruleAdjustments = [];
        foreach ($rules as $rule) {
            if (! is_array($rule) || ! $this->matchesConditions($rule['conditions'] ?? [], $input, $context)) {
                continue;
            }
            $before = $price;
            $type = trim((string) ($rule['type'] ?? $rule['adjustment_type'] ?? ''));
            $value = (float) ($rule['value'] ?? $rule['adjustment_value'] ?? 0);
            $price = match ($type) {
                'fixed_minor' => $price + (int) round($value),
                'percentage' => (int) round($price * (1 + ($value / 100))),
                'multiplier' => (int) round($price * $this->boundedMultiplier($value, 'rule multiplier')),
                default => throw new InvalidArgumentException("Unsupported pricing rule type [{$type}]."),
            };
            $price = max(0, $price);
            if ($price !== $before) {
                $ruleId = trim((string) ($rule['id'] ?? $rule['public_id'] ?? 'rule-' . count($appliedRuleIds)));
                $appliedRuleIds[] = $ruleId;
                $ruleAdjustments[] = [
                    'id' => $ruleId,
                    'priority' => (int) ($rule['priority'] ?? 100),
                    'type' => $type,
                    'value' => $value,
                    'before_minor' => $before,
                    'after_minor' => $price,
                ];
            }
        }

        $seasonalMultiplier = $this->boundedMultiplier((float) ($input['seasonal_multiplier'] ?? 1.0), 'seasonal_multiplier');
        $price = (int) round($price * $seasonalMultiplier);

        $occupancyPercentage = $this->boundedScore((float) ($input['occupancy_percentage'] ?? 0));
        $occupancyMultiplier = $this->occupancyMultiplier($occupancyPercentage);
        $price = (int) round($price * $occupancyMultiplier);

        $demandScore = $this->boundedScore((float) ($input['demand_score'] ?? 50));
        [$demandLevel, $demandMultiplier] = $this->demandFactor($demandScore);
        $price = (int) round($price * $demandMultiplier);

        $minimum = $this->nullableInteger($input['minimum_price_minor'] ?? null, 'minimum_price_minor');
        $maximum = $this->nullableInteger($input['maximum_price_minor'] ?? null, 'maximum_price_minor');
        if ($minimum !== null && $maximum !== null && $minimum > $maximum) {
            throw new InvalidArgumentException('minimum_price_minor cannot exceed maximum_price_minor.');
        }

        $minimumApplied = $minimum !== null && $price < $minimum;
        $maximumApplied = $maximum !== null && $price > $maximum;
        if ($minimumApplied) {
            $price = $minimum;
        }
        if ($maximumApplied) {
            $price = $maximum;
        }

        $factors = [
            'rule_adjustments' => $ruleAdjustments,
            'seasonal_multiplier' => $seasonalMultiplier,
            'occupancy_percentage' => $occupancyPercentage,
            'occupancy_multiplier' => $occupancyMultiplier,
            'demand_score' => $demandScore,
            'demand_level' => $demandLevel,
            'demand_multiplier' => $demandMultiplier,
            'minimum_price_minor' => $minimum,
            'maximum_price_minor' => $maximum,
        ];
        $hashPayload = [
            'base_price_minor' => $basePrice,
            'final_price_minor' => $price,
            'currency' => $currency,
            'applied_rule_ids' => $appliedRuleIds,
            'factors' => $factors,
        ];

        return new PricingDecision(
            basePriceMinor: $basePrice,
            finalPriceMinor: $price,
            currency: $currency,
            appliedRuleIds: $appliedRuleIds,
            seasonalMultiplier: $seasonalMultiplier,
            occupancyMultiplier: $occupancyMultiplier,
            demandMultiplier: $demandMultiplier,
            demandScore: $demandScore,
            demandLevel: $demandLevel,
            minimumBoundApplied: $minimumApplied,
            maximumBoundApplied: $maximumApplied,
            factors: $factors,
            decisionHash: hash('sha256', $this->canonicalJson($hashPayload)),
        );
    }

    /** @param mixed $conditions @param array<string,mixed> $input @param array<string,mixed> $context */
    private function matchesConditions(mixed $conditions, array $input, array $context): bool
    {
        if (! is_array($conditions) || $conditions === []) {
            return true;
        }
        $values = $context + [
            'occupancy_percentage' => (float) ($input['occupancy_percentage'] ?? 0),
            'demand_score' => (float) ($input['demand_score'] ?? 50),
            'target_type' => (string) ($input['target_type'] ?? ''),
            'target_reference' => (string) ($input['target_reference'] ?? ''),
            'channel' => (string) ($input['channel'] ?? ''),
        ];
        foreach ($conditions as $key => $expected) {
            $key = (string) $key;
            if (str_ends_with($key, '_min')) {
                $actualKey = substr($key, 0, -4);
                if ((float) ($values[$actualKey] ?? 0) < (float) $expected) {
                    return false;
                }
                continue;
            }
            if (str_ends_with($key, '_max')) {
                $actualKey = substr($key, 0, -4);
                if ((float) ($values[$actualKey] ?? 0) > (float) $expected) {
                    return false;
                }
                continue;
            }
            $actual = $values[$key] ?? null;
            if (is_array($expected)) {
                if (! in_array($actual, $expected, true)) {
                    return false;
                }
            } elseif ((string) $actual !== (string) $expected) {
                return false;
            }
        }
        return true;
    }

    private function occupancyMultiplier(float $percentage): float
    {
        return match (true) {
            $percentage < 30 => 0.85,
            $percentage < 50 => 0.92,
            $percentage < 70 => 1.00,
            $percentage < 85 => 1.08,
            $percentage < 95 => 1.15,
            default => 1.25,
        };
    }

    /** @return array{0:string,1:float} */
    private function demandFactor(float $score): array
    {
        return match (true) {
            $score < 20 => ['very_low', 0.90],
            $score < 40 => ['low', 0.97],
            $score < 60 => ['normal', 1.00],
            $score < 80 => ['elevated', 1.05],
            default => ['high', 1.10],
        };
    }

    private function boundedMultiplier(float $value, string $field): float
    {
        if (! is_finite($value) || $value < 0.10 || $value > 10.0) {
            throw new InvalidArgumentException("{$field} must be between 0.10 and 10.0.");
        }
        return round($value, 4);
    }

    private function boundedScore(float $value): float
    {
        if (! is_finite($value)) {
            throw new InvalidArgumentException('Pricing scores must be finite.');
        }
        return round(max(0, min(100, $value)), 2);
    }

    private function integer(mixed $value, string $field, int $minimum): int
    {
        if (! is_int($value) && ! (is_string($value) && preg_match('/^-?\d+$/', $value))) {
            throw new InvalidArgumentException("{$field} must be an integer minor-unit amount.");
        }
        $integer = (int) $value;
        if ($integer < $minimum) {
            throw new InvalidArgumentException("{$field} must be at least {$minimum}.");
        }
        return $integer;
    }

    private function nullableInteger(mixed $value, string $field): ?int
    {
        return $value === null || $value === '' ? null : $this->integer($value, $field, 0);
    }

    /** @param array<string,mixed> $value */
    private function canonicalJson(array $value): string
    {
        $sort = static function (mixed &$item) use (&$sort): void {
            if (! is_array($item)) {
                return;
            }
            foreach ($item as &$child) {
                $sort($child);
            }
            unset($child);
            if (! array_is_list($item)) {
                ksort($item);
            }
        };
        $sort($value);
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }
}
