# Discount Manager - Calculating Engine Integration

## Overview

The DiscountManager extension has been refactored to integrate as the first pluggable Calculating Engine in the WorkCore Calculating Engine Framework. This integration enables seamless discount calculations within a standardized pricing pipeline architecture.

## Architecture

### Engine Structure

The `DiscountCalculatingEngine` implements the `CalculatingEngineContract` and provides:

- **Engine ID**: `discount_engine`
- **Engine Name**: Discount Engine
- **Engine Type**: `discount`
- **Priority**: 20 (runs after dynamic pricing at priority 100, before tax)
- **Version**: 1.0.0

### Contract Implementation

The engine implements all required methods from `CalculatingEngineContract`:

```php
- getEngineId(): string
- getEngineName(): string
- getEngineVersion(): string
- getEngineType(): string
- isEnabled(): bool
- shouldApply(PricingContextContract): bool
- calculate(PricingContextContract): CalculatingEngineResult
- getConfig(): array
- getMetadata(): array
- getPriority(): int
- hasConflictWith(string): bool
- getConflictResolution(string): string
```

## Integration Points

### 1. Service Registration

The `DiscountManagerServiceProvider` now registers the engine during the `register()` phase:

```php
public function register(): void
{
    $this->registerDiscountCalculatingEngine();
}

private function registerDiscountCalculatingEngine(): void
{
    $this->app->afterResolving(
        CalculatingEngineService::class,
        function ($service) {
            $service->registerEngine(
                'discount_engine',
                DiscountCalculatingEngine::class
            );
        }
    );
}
```

### 2. Configuration

The engine configuration is centralized in `config/calculating-engines.php`:

```php
'discount_engine' => [
    'enabled' => true,
    'priority' => 20,
    'class' => DiscountCalculatingEngine::class,
    'config' => [
        'enabled' => true,
        'allow_multiple_discounts' => false,
        'max_discount_percentage' => 100,
        'min_discount_amount' => 0,
    ],
],
```

### 3. Pipeline Integration

The engine is automatically integrated into the pricing calculation pipeline:

```php
// Using CalculatingEngineService
$service = app(CalculatingEngineService::class);

// Calculate with all engines
$result = $service->calculateWithDefaults($pricingContext);

// Calculate with specific engine
$result = $service->calculateWithEngine($pricingContext, 'discount_engine');

// Calculate by type
$result = $service->calculateByType($pricingContext, 'discount');
```

### 4. DiscountService Refactoring

The `DiscountService` now delegates to the `DiscountCalculatingEngine` while maintaining backward compatibility:

```php
public static function applyDiscountCoupon(): ?Coupon
{
    // Delegates to engine logic
    // Falls back to direct validation if engine unavailable
}
```

## Pricing Context Requirements

When using the discount engine, the `PricingContext` should include:

```php
$context = new PricingContext(
    'company-id',
    100.00,  // base price
    ['type' => 'product', 'id' => 1],  // resource
    [
        'user_id' => 1,                        // User ID (optional)
        'plan_id' => '1',                      // Pricing plan ID (optional)
        'payment_gateway' => 'stripe',         // Payment gateway (optional)
        'subscription_active' => false,        // Subscription status (optional)
        'url' => request()->url(),             // Current URL (optional)
    ]
);
```

## Engine Result

The engine returns a `CalculatingEngineResult` with:

```php
CalculatingEngineResult {
    engine_id: 'discount_engine',
    successful: bool,
    calculated_price: float,
    base_price: float,
    adjustment_amount: float,  // Discount amount
    adjustment_percentage: float,
    reason: string,
    factors: array,
    details: array,
    applied_conditions: array,
    error_message: ?string,
    execution_time: float (ms),
}
```

Example result:
```php
[
    'engine_id' => 'discount_engine',
    'successful' => true,
    'calculated_price' => 85.00,
    'base_price' => 100.00,
    'adjustment_amount' => -15.00,
    'adjustment_percentage' => -15.00,
    'reason' => 'Discount applied: Summer Sale (Coupon: SUMMER15, Amount: $15.00)',
    'factors' => [
        'discount_type' => 'percentage',
        'discount_amount' => 15.00,
        'coupon_code' => 'SUMMER15',
        'coupon_discount_value' => 15,
    ],
    'details' => [
        'coupon_id' => 1,
        'discount_id' => 5,
        'discount_reason' => 'Conditional discount applied',
        'conditions_met' => [
            'user_type' => true,
            'payment_gateway' => true,
            'pricing_plan' => true,
        ],
    ],
    'applied_conditions' => [
        'discount_id' => 5,
        'coupon_id' => 1,
        'coupon_code' => 'SUMMER15',
    ],
]
```

## Engine Logic Flow

1. **Availability Check**: Verifies if discounts are active in the database
2. **Discount Discovery**: Retrieves all active, non-scheduled discounts ordered by amount (highest first)
3. **Eligibility Validation**:
   - Checks if coupon is not null
   - Validates usage limits
   - Checks per-user usage restrictions
   - Validates subscription status
4. **Condition Evaluation**:
   - Payment gateway match
   - User type (new/inactive)
   - Pricing plan match
   - Conditional logic (AND/OR)
5. **Discount Calculation**:
   - Determines discount type (fixed/percentage)
   - Calculates discount amount
   - Computes adjusted price
6. **Result Assembly**: Returns comprehensive result with factors and metadata

## Conflict Resolution

The discount engine may conflict with:
- `promotion_engine`
- `loyalty_engine`

Conflict resolution strategy: `maximum` (uses the discount that results in the lowest price)

## Priority Execution Order

Engines execute in priority order (lower = runs first):

1. **Dynamic Pricing** (priority: 100)
2. **Discount Engine** (priority: 20)
3. **Tax Engine** (priority: 5) - Future implementation
4. **Loyalty Engine** (priority: 25) - Future implementation

This means discounts are applied to the dynamically priced base, which makes semantic sense.

## Backward Compatibility

The refactoring maintains 100% backward compatibility:

- `DiscountService::applyDiscountCoupon()` works as before
- `DiscountService::checkDiscountConditionsFor()` works as before
- All existing DiscountManager routes and controllers unchanged
- All existing models unchanged
- No database migrations required

If the engine is unavailable, the service falls back to direct validation logic.

## Configuration

### Global Enable/Disable

In `config/discount-manager.php`:
```php
'enabled' => true  // Controls engine availability
```

### Engine-Specific Config

In `config/calculating-engines.php`:
```php
'engines' => [
    'discount_engine' => [
        'enabled' => true,
        'priority' => 20,
        'config' => [
            'allow_multiple_discounts' => false,
            'max_discount_percentage' => 100,
        ],
    ],
]
```

### Pipeline Configuration

```php
'pipeline' => [
    'stop_on_error' => false,
    'allow_conflicts' => false,
    'conflict_resolution' => 'maximum',
]
```

## Testing

### Unit Tests

Location: `tests/Unit/Calculating/DiscountCalculatingEngineTest.php`

Tests cover:
- Engine identity and metadata
- Priority and execution order
- Configuration handling
- Conflict detection
- Basic calculation

### Feature Tests

Location: `tests/Feature/Calculating/DiscountCalculatingEnginePipelineTest.php`

Tests cover:
- Engine registration
- Pipeline integration
- Price history tracking
- Execution logging
- Metadata retrieval

### Running Tests

```bash
# Unit tests only
php artisan test tests/Unit/Calculating/DiscountCalculatingEngineTest.php

# Feature tests only
php artisan test tests/Feature/Calculating/DiscountCalculatingEnginePipelineTest.php

# All calculating tests
php artisan test tests/Unit/Calculating/ tests/Feature/Calculating/
```

## Usage Examples

### Basic Usage with Pipeline

```php
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineService;
use App\Domains\WorkCore\Calculating\Services\PricingContext;

$service = app(CalculatingEngineService::class);

$context = new PricingContext(
    'company-1',
    99.99,
    ['type' => 'product'],
    [
        'user_id' => auth()->id(),
        'plan_id' => '1',
        'payment_gateway' => 'stripe',
        'subscription_active' => false,
    ]
);

// Execute all enabled engines
$pipelineResult = $service->calculateWithDefaults($context);

if ($pipelineResult->isSuccessful()) {
    $finalPrice = $context->getCurrentPrice();
    $appliedEngines = $context->getAppliedEngines();
    $priceHistory = $context->getPriceHistory();
}
```

### Single Engine Usage

```php
$service = app(CalculatingEngineService::class);

$context = new PricingContext('company-1', 99.99);

$pipelineResult = $service->calculateWithEngine($context, 'discount_engine');
```

### Engine-Type Based Calculation

```php
$service = app(CalculatingEngineService::class);

// Calculate using all discount engines
$pipelineResult = $service->calculateByType($context, 'discount');
```

### Direct Engine Access

```php
$engine = app(CalculatingEngineService::class)->getEngine('discount_engine');

if ($engine->isEnabled() && $engine->shouldApply($context)) {
    $result = $engine->calculate($context);
}
```

## Future Extensions

The framework is designed to support multiple calculating engines:

### Tax Engine (Priority: 5)
```php
// Apply tax after discount
'tax_engine' => [
    'enabled' => true,
    'priority' => 5,
    'class' => TaxEngine::class,
]
```

### Loyalty Engine (Priority: 25)
```php
// Apply loyalty rewards/points
'loyalty_engine' => [
    'enabled' => true,
    'priority' => 25,
    'class' => LoyaltyEngine::class,
]
```

### Promotion Engine (Priority: 30)
```php
// Apply promotional pricing
'promotion_engine' => [
    'enabled' => true,
    'priority' => 30,
    'class' => PromotionEngine::class,
]
```

## Performance Considerations

1. **Query Optimization**: The engine uses orderBy and early exit patterns
2. **Caching**: Results can be cached via pipeline configuration
3. **Lazy Loading**: Engines are instantiated only when needed
4. **Exit Early**: Validation fails fast on first condition failure

## Monitoring and Debugging

### Logging

Enable engine logging in `config/calculating-engines.php`:
```php
'logging' => [
    'enabled' => true,
    'channel' => 'calculating_engines',
    'log_level' => 'debug',
    'log_pipeline_results' => true,
]
```

### Execution Time Tracking

Each engine tracks its execution time:
```php
$result->getExecutionTime(); // Returns milliseconds
```

### Price History

Track price changes through the pipeline:
```php
$priceHistory = $context->getPriceHistory();
// [
//   ['engine_id' => 'initial', 'price' => 100.00, ...],
//   ['engine_id' => 'discount_engine', 'price' => 85.00, ...],
// ]
```

## Migration Path

No migration required! The engine integrates seamlessly:

1. DiscountManagerServiceProvider automatically registers engine
2. Existing DiscountService delegates to engine
3. All existing routes/controllers work unchanged
4. Optional: Use new engine API for new features

## Files Changed/Created

### Created
- `/app/Domains/WorkCore/Calculating/Engines/DiscountCalculatingEngine.php`
- `/config/calculating-engines.php`
- `/tests/Unit/Calculating/DiscountCalculatingEngineTest.php`
- `/tests/Feature/Calculating/DiscountCalculatingEnginePipelineTest.php`
- `DISCOUNT_ENGINE_INTEGRATION.md` (this file)

### Modified
- `/app/extensions/DiscountManager/System/Services/DiscountService.php`
- `/app/extensions/DiscountManager/System/DiscountManagerServiceProvider.php`

### No Changes Required
- Models (Discount, ConditionalDiscount, Coupon)
- Controllers (DiscountManagerController)
- Routes
- Views
- Database schema

## Success Checklist

- [x] DiscountCalculatingEngine implements CalculatingEngineContract
- [x] Engine extracts logic from DiscountService
- [x] Engine registered in CalculatingEngineService
- [x] Pipeline executes discount engine with correct priority
- [x] DiscountManager UI still works (backward compatible)
- [x] Unit tests passing for engine
- [x] Feature tests passing for pipeline
- [x] Configuration system in place
- [x] Ready for additional engines (Tax, Loyalty, Promotion, etc)

## Support and Questions

For issues or questions about the discount engine integration, refer to:
- WorkCore Calculating Engine Framework documentation
- DiscountManager extension documentation
- Test files for usage examples
