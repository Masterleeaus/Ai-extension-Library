<?php

namespace App\Domains\WorkCore\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Support\Str;

class PricingRule extends Model
{
    use SoftDeletes;

    protected $table = 'workcore_pricing_rules';

    protected $fillable = [
        'public_id',
        'company_id',
        'name',
        'description',
        'conditions',
        'adjustments',
        'priority',
        'is_active',
        'starts_at',
        'ends_at',
        'rule_type',
        'min_adjustment',
        'max_adjustment',
        'metadata',
        'created_by_user_id',
    ];

    protected $casts = [
        'conditions' => 'json',
        'adjustments' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (! $model->public_id) {
                $model->public_id = Str::ulid();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }

    /**
     * Evaluate if this rule applies to the given context
     */
    public function evaluate(array $context): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();
        if ($this->starts_at && $now < $this->starts_at) {
            return false;
        }

        if ($this->ends_at && $now > $this->ends_at) {
            return false;
        }

        return $this->evaluateConditions($context);
    }

    /**
     * Evaluate the conditions JSON against the context
     */
    protected function evaluateConditions(array $context): bool
    {
        $conditions = $this->conditions ?? [];

        foreach ($conditions as $condition) {
            if (! $this->evaluateSingleCondition($condition, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateSingleCondition(array $condition, array $context): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;

        if (! isset($context[$field])) {
            return false;
        }

        $contextValue = $context[$field];

        return match ($operator) {
            '=' => $contextValue == $value,
            '!=' => $contextValue != $value,
            '>' => $contextValue > $value,
            '>=' => $contextValue >= $value,
            '<' => $contextValue < $value,
            '<=' => $contextValue <= $value,
            'in' => in_array($contextValue, $value),
            'not_in' => ! in_array($contextValue, $value),
            'between' => $contextValue >= $value[0] && $contextValue <= $value[1],
            default => false,
        };
    }

    /**
     * Apply this rule to get adjusted price
     */
    public function applyRule(float $basePrice, array $context): float
    {
        if (! $this->evaluate($context)) {
            return $basePrice;
        }

        $adjustments = $this->adjustments ?? [];
        $adjustedPrice = $basePrice;

        foreach ($adjustments as $adjustment) {
            $adjustedPrice = $this->applyAdjustment($adjustedPrice, $adjustment);
        }

        // Apply min/max bounds
        if ($this->min_adjustment !== null) {
            $adjustedPrice = max($adjustedPrice, $this->min_adjustment);
        }

        if ($this->max_adjustment !== null) {
            $adjustedPrice = min($adjustedPrice, $this->max_adjustment);
        }

        return $adjustedPrice;
    }

    /**
     * Apply a single adjustment
     */
    protected function applyAdjustment(float $price, array $adjustment): float
    {
        $type = $adjustment['type'] ?? 'percentage';
        $value = $adjustment['value'] ?? 0;

        return match ($type) {
            'percentage' => $price * (1 + $value / 100),
            'fixed' => $price + $value,
            'multiplier' => $price * $value,
            default => $price,
        };
    }

    /**
     * Get all matching rules for a context, ordered by priority
     */
    public static function getMatchingRules(string $companyId, array $context): array
    {
        return static::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get()
            ->filter(fn ($rule) => $rule->evaluate($context))
            ->all();
    }

    /**
     * Scope: active rules only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: by rule type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Scope: ordered by priority
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}
