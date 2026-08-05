<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

final class PricingInputValidator
{
    /** @param array<string,mixed> $payload */
    public function validatePriceInput(array $payload): void
    {
        $this->integer($payload['base_price_minor'] ?? null, 'base_price_minor', 0);
        $this->requiredString($payload['target_type'] ?? null, 'target_type');
        $this->requiredString($payload['target_reference'] ?? null, 'target_reference');

        if (array_key_exists('currency', $payload) && $payload['currency'] !== null && $payload['currency'] !== '') {
            $currency = trim((string) $payload['currency']);
            if (preg_match('/^[A-Za-z]{3}$/', $currency) !== 1) {
                throw new InvalidArgumentException('currency must be a three-letter ISO code.');
            }
        }

        $minimum = $this->optionalInteger($payload['minimum_price_minor'] ?? null, 'minimum_price_minor', 0);
        $maximum = $this->optionalInteger($payload['maximum_price_minor'] ?? null, 'maximum_price_minor', 0);
        if ($minimum !== null && $maximum !== null && $minimum > $maximum) {
            throw new InvalidArgumentException('minimum_price_minor cannot exceed maximum_price_minor.');
        }
    }

    /** @param array<string,mixed> $payload */
    public function validateRule(array $payload): void
    {
        $this->requiredString($payload['name'] ?? null, 'name');
        $type = $this->requiredString($payload['adjustment_type'] ?? null, 'adjustment_type');
        if (! in_array($type, ['fixed_minor', 'percentage', 'multiplier'], true)) {
            throw new InvalidArgumentException('Unsupported pricing adjustment type.');
        }

        $raw = $payload['adjustment_value'] ?? null;
        $value = $this->number($raw, 'adjustment_value');
        $valid = match ($type) {
            'fixed_minor' => $this->isIntegerValue($raw) && abs($value) <= 9_999_999_999,
            'percentage' => $value >= -100.0 && $value <= 1000.0,
            'multiplier' => $value >= 0.10 && $value <= 10.0,
        };
        if (! $valid) {
            throw new InvalidArgumentException('The adjustment value is invalid for the selected adjustment type.');
        }

        $this->assertDateTimeWindow($payload['starts_at'] ?? null, $payload['ends_at'] ?? null, 'starts_at', 'ends_at');
    }

    /** @param array<string,mixed> $payload */
    public function validateSeasonalRate(array $payload): void
    {
        $this->requiredString($payload['name'] ?? null, 'name');
        $startsOn = $this->date($payload['starts_on'] ?? null, 'starts_on');
        $endsOn = $this->date($payload['ends_on'] ?? null, 'ends_on');
        if ($startsOn > $endsOn) {
            throw new InvalidArgumentException('starts_on cannot be after ends_on.');
        }

        $multiplier = $this->number($payload['multiplier'] ?? null, 'multiplier');
        if ($multiplier < 0.10 || $multiplier > 10.0) {
            throw new InvalidArgumentException('multiplier must be between 0.10 and 10.0.');
        }
    }

    /** @param array<string,mixed> $payload */
    public function validateSignal(array $payload): void
    {
        $type = $this->requiredString($payload['signal_type'] ?? null, 'signal_type');
        if (! in_array($type, ['demand', 'occupancy', 'competitor'], true)) {
            throw new InvalidArgumentException('signal_type must be demand, occupancy or competitor.');
        }
        $this->requiredString($payload['target_type'] ?? null, 'target_type');
        $this->requiredString($payload['target_reference'] ?? null, 'target_reference');

        match ($type) {
            'demand' => $this->validateDemandSignal($payload),
            'occupancy' => $this->validateOccupancySignal($payload),
            'competitor' => $this->validateCompetitorSignal($payload),
        };
    }

    /** @param array<string,mixed> $payload */
    private function validateDemandSignal(array $payload): void
    {
        $score = $this->number($payload['score'] ?? null, 'score');
        if ($score < 0.0 || $score > 100.0) {
            throw new InvalidArgumentException('score must be between 0 and 100.');
        }
        if (array_key_exists('quantity', $payload)) {
            $this->integer($payload['quantity'], 'quantity', 0);
        }
    }

    /** @param array<string,mixed> $payload */
    private function validateOccupancySignal(array $payload): void
    {
        $capacity = $this->integer($payload['capacity'] ?? null, 'capacity', 1);
        $occupied = $this->integer($payload['occupied'] ?? null, 'occupied', 0);
        if ($occupied > $capacity) {
            throw new InvalidArgumentException('occupied cannot exceed capacity.');
        }
    }

    /** @param array<string,mixed> $payload */
    private function validateCompetitorSignal(array $payload): void
    {
        $this->requiredString($payload['competitor_name'] ?? null, 'competitor_name');
        $this->integer($payload['observed_price_minor'] ?? null, 'observed_price_minor', 0);
        $currency = $this->requiredString($payload['currency'] ?? null, 'currency');
        if (preg_match('/^[A-Za-z]{3}$/', $currency) !== 1) {
            throw new InvalidArgumentException('currency must be a three-letter ISO code.');
        }
    }

    private function assertDateTimeWindow(mixed $start, mixed $end, string $startField, string $endField): void
    {
        if (($start === null || $start === '') && ($end === null || $end === '')) {
            return;
        }
        $startValue = $start === null || $start === '' ? null : $this->dateTime($start, $startField);
        $endValue = $end === null || $end === '' ? null : $this->dateTime($end, $endField);
        if ($startValue !== null && $endValue !== null && $startValue > $endValue) {
            throw new InvalidArgumentException("{$startField} cannot be after {$endField}.");
        }
    }

    private function requiredString(mixed $value, string $field): string
    {
        $string = trim((string) ($value ?? ''));
        if ($string === '') {
            throw new InvalidArgumentException("{$field} is required.");
        }

        return $string;
    }

    private function number(mixed $value, string $field): float
    {
        if (! is_int($value) && ! is_float($value) && ! (is_string($value) && is_numeric($value))) {
            throw new InvalidArgumentException("{$field} must be numeric.");
        }
        $number = (float) $value;
        if (! is_finite($number)) {
            throw new InvalidArgumentException("{$field} must be finite.");
        }

        return $number;
    }

    private function integer(mixed $value, string $field, int $minimum): int
    {
        if (! $this->isIntegerValue($value)) {
            throw new InvalidArgumentException("{$field} must be an integer.");
        }
        $integer = (int) $value;
        if ($integer < $minimum) {
            throw new InvalidArgumentException("{$field} must be at least {$minimum}.");
        }

        return $integer;
    }

    private function optionalInteger(mixed $value, string $field, int $minimum): ?int
    {
        return $value === null || $value === '' ? null : $this->integer($value, $field, $minimum);
    }

    private function isIntegerValue(mixed $value): bool
    {
        return is_int($value)
            || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1)
            || (is_float($value) && is_finite($value) && floor($value) === $value);
    }

    private function date(mixed $value, string $field): DateTimeImmutable
    {
        $raw = trim((string) ($value ?? ''));
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $raw) {
            throw new InvalidArgumentException("{$field} must use YYYY-MM-DD format.");
        }

        return $date;
    }

    private function dateTime(mixed $value, string $field): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable(trim((string) $value));
        } catch (Throwable) {
            throw new InvalidArgumentException("{$field} must be a valid date and time.");
        }
    }
}
