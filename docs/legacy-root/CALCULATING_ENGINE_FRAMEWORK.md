# WorkCore Calculating Engine Framework

Complete pluggable architecture for 10-20+ calculating engines supporting dynamic pricing, discounts, taxes, loyalty, promotions, and subscriptions.

## Overview

The Calculating Engine Framework provides a unified, extensible platform for orchestrating multiple pricing calculation engines across WorkCore. Each engine implements a common contract and executes in a priority-ordered pipeline, enabling complex pricing logic without tight coupling.

### Core Concepts

- **Engine**: Pluggable calculation component implementing `CalculatingEngineContract`
- **Pipeline**: Orchestrates engine execution with conflict detection and resolution
- **Context**: Shared state (`PricingContextContract`) passed through pipeline
- **Registry**: Factory for registering and retrieving engines
- **Service**: High-level API for calculation orchestration

## Architecture

```
                    ┌─────────────────┐
                    │   Application   │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │ Calculating     │
                    │ EngineService   │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │ Pipeline        │
                    │ Executor        │
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
    ┌───▼───┐          ┌─────▼──────┐         ┌──▼────┐
    │Tax    │ Pri: 5   │Subscription│ Pri: 15 │Others │
    │Engine │          │Engine      │         │...    │
    └───────┘          └────────────┘         └───────┘
```

## Built-in Engines

### 1. Discount Engine (Priority: 20)
**Status**: ✅ Implemented

Applies conditional discounts and coupon-based pricing adjustments.

```php
$result = $service->calculateWithEngine($context, 'discount_engine');
```

**Features**:
- Conditional discount matching
- Coupon validation and usage tracking
- Per-user restrictions
- Scheduled discounts
- 100% backward compatible with DiscountManager

**Configuration**:
```php
'discount_engine' => [
    'enabled' => true,
    'priority' => 20,
    'config' => [
        'allow_multiple_discounts' => false,
        'max_discount_percentage' => 100,
    ],
]
```

---

### 2. Dynamic Pricing Engine (Priority: 100)
**Status**: ✅ Implemented

Real-time demand-based and occupancy-based pricing.

```php
$result = $service->calculateWithEngine($context, 'dynamic_pricing');
```

**Features**:
- Demand scoring (0-100)
- Occupancy-based pricing tiers
- Seasonal rate adjustments
- Revenue optimization algorithms
- Price elasticity calculations

**Configuration**:
```php
'dynamic_pricing' => [
    'enabled' => true,
    'priority' => 100,
    'config' => [
        'enable_demand' => true,
        'enable_seasonal' => true,
        'enable_occupancy' => true,
        'min_price' => null,
        'max_price' => null,
    ],
]
```

---

### 3. Tax Engine (Priority: 5)
**Status**: 🔧 Scaffolded - Ready for Implementation

Calculates and applies regional taxes.

```php
$result = $service->calculateWithEngine($context, 'tax_engine');
```

**Features**:
- Region-based tax rates
- Product tax classes
- Tax exemptions
- Tax reporting
- Multiple tax jurisdictions

**TODO**:
- [ ] Create TaxRate model and migrations
- [ ] Implement tax rate lookup service
- [ ] Add regional tax database
- [ ] Support product tax classes
- [ ] Create tests and API endpoints
- [ ] Add tax reporting analytics

---

### 4. Loyalty Engine (Priority: 25)
**Status**: 🔧 Scaffolded - Ready for Implementation

Applies loyalty tier discounts and points-based rewards.

```php
$result = $service->calculateWithEngine($context, 'loyalty_engine');
```

**Features**:
- Tier-based discounts (bronze, silver, gold, platinum)
- Points redemption
- Points earning tracking
- Tier progression
- Member-exclusive pricing

**TODO**:
- [ ] Create LoyaltyTier and LoyaltyPoints models
- [ ] Implement points calculation service
- [ ] Add tier progression logic
- [ ] Create points redemption service
- [ ] Add member history tracking
- [ ] Create tests and API endpoints

---

### 5. Promotion Engine (Priority: 30)
**Status**: 🔧 Scaffolded - Ready for Implementation

Applies time-limited promotional discounts and bulk pricing.

```php
$result = $service->calculateWithEngine($context, 'promotion_engine');
```

**Features**:
- Time-limited promotions
- Promotion codes
- Percentage and fixed discounts
- Bulk/quantity-based pricing
- Stacking rules

**TODO**:
- [ ] Create Promotion model and migrations
- [ ] Implement promotion matching service
- [ ] Add promotion code validation
- [ ] Implement bulk pricing tiers
- [ ] Add time window checking
- [ ] Create tests and API endpoints

---

### 6. Subscription Engine (Priority: 15)
**Status**: 🔧 Scaffolded - Ready for Implementation

Applies subscription plan pricing and recurring billing rates.

```php
$result = $service->calculateWithEngine($context, 'subscription_engine');
```

**Features**:
- Plan-based pricing
- Cycle-based discounts (annual vs monthly)
- Proration calculation
- Usage tracking
- Recurring billing

**TODO**:
- [ ] Create SubscriptionPlan model
- [ ] Implement pricing service
- [ ] Add proration calculation
- [ ] Implement usage tracking
- [ ] Add billing cycle management
- [ ] Create tests and API endpoints

---

## Usage Examples

### Basic Calculation with All Engines

```php
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineService;
use App\Domains\WorkCore\Calculating\Services\PricingContext;

$service = app(CalculatingEngineService::class);

$context = new PricingContext(
    basePrice: 100.00,
    companyId: 'acme-corp',
    resource: ['type' => 'subscription', 'id' => 123],
);

$context
    ->set('user_id', 456)
    ->set('country', 'US')
    ->set('state', 'CA')
    ->set('subscription_active', true)
    ->set('loyalty_tier', 'gold');

$result = $service->calculateWithDefaults($context);

echo "Final Price: $" . $result->getFinalPrice();
// Output: Final Price: $75.50
```

### Single Engine Calculation

```php
$result = $service->calculateWithEngine($context, 'discount_engine');
```

### Multiple Engines

```php
$result = $service->calculate(
    $context,
    ['tax_engine', 'discount_engine', 'loyalty_engine'],
    ['stop_on_error' => false, 'conflict_resolution' => 'maximum']
);
```

### By Type

```php
// Apply all discount-type engines
$result = $service->calculateByType($context, 'discount');
```

## Configuration

### Global Config (`config/calculating-engines.php`)

```php
return [
    'engines' => [
        'discount_engine' => [
            'enabled' => true,
            'priority' => 20,
            'class' => DiscountCalculatingEngine::class,
            'config' => [
                'allow_multiple_discounts' => false,
                'max_discount_percentage' => 100,
            ],
        ],
        // ... other engines
    ],

    'pipeline' => [
        'stop_on_error' => false,
        'allow_conflicts' => false,
        'conflict_resolution' => 'maximum', // 'maximum', 'minimum', 'average', 'first', 'last'
    ],

    'logging' => [
        'enabled' => true,
        'channel' => 'calculating_engines',
        'log_level' => 'debug',
    ],

    'caching' => [
        'enabled' => false,
        'ttl' => 3600,
        'driver' => 'redis',
    ],
];
```

## Creating Custom Engines

Implement `CalculatingEngineContract`:

```php
<?php

namespace App\Domains\WorkCore\Calculating\Engines;

use App\Domains\WorkCore\Calculating\Contracts\CalculatingEngineContract;
use App\Domains\WorkCore\Calculating\Contracts\PricingContextContract;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineResult;

class CustomCalculatingEngine implements CalculatingEngineContract
{
    public function getEngineId(): string { return 'custom_engine'; }
    public function getEngineName(): string { return 'Custom Engine'; }
    public function getEngineVersion(): string { return '1.0.0'; }
    public function getEngineType(): string { return 'custom'; }
    public function isEnabled(): bool { return true; }
    
    public function shouldApply(PricingContextContract $context): bool
    {
        return true;
    }

    public function calculate(PricingContextContract $context): CalculatingEngineResult
    {
        $basePrice = $context->getCurrentPrice();
        $adjustedPrice = $basePrice * 0.9; // 10% discount

        return CalculatingEngineResult::success(
            $this->getEngineId(),
            $basePrice,
            $adjustedPrice,
            'Custom discount applied',
            ['discount_rate' => 0.1],
            []
        );
    }

    public function getConfig(): array { return []; }
    public function getMetadata(): array { return []; }
    public function getPriority(): int { return 50; }
    public function hasConflictWith(string $engineId): bool { return false; }
    public function getConflictResolution(string $engineId): string { return 'maximum'; }
}
```

Register in config:

```php
'custom_engine' => [
    'enabled' => true,
    'priority' => 50,
    'class' => CustomCalculatingEngine::class,
],
```

## Testing

Run framework tests:

```bash
php artisan test tests/Unit/Calculating/
php artisan test tests/Feature/Calculating/
```

## Execution Flow

1. **Input**: `PricingContext` with base price and attributes
2. **Registry**: Fetch enabled engines in priority order
3. **Pipeline**: Execute engines sequentially
4. **Conflict Detection**: Check for conflicting adjustments
5. **Conflict Resolution**: Apply resolution strategy
6. **Output**: `CalculatingEnginePipelineResult` with all adjustments

## Performance Considerations

- **Caching**: Enable caching in config for repeated calculations
- **Parallel Execution**: Future enhancement for independent engines
- **Query Optimization**: Engines should use indexed lookups
- **Early Exit**: Use `shouldApply()` to skip unnecessary calculations

## Integration Points

- **CRM**: Customer loyalty tier, subscription status
- **Finance**: Tax rates, invoicing, payment status
- **E-Commerce**: Product type, inventory level
- **Subscriptions**: Plan details, billing cycle
- **Loyalty**: Points balance, tier status

## Roadmap

### Phase 1 - Scaffolding (CURRENT)
- ✅ Framework contracts and services
- ✅ Discount and Dynamic Pricing engines
- ✅ Tax, Loyalty, Promotion, Subscription engine stubs
- ✅ Configuration system
- ✅ Documentation

### Phase 2 - Implement Remaining Engines
- [ ] Tax Engine (regional rates, product classes)
- [ ] Loyalty Engine (points, tiers, redemption)
- [ ] Promotion Engine (time-limited, codes, bulk)
- [ ] Subscription Engine (plans, proration, usage)

### Phase 3 - Advanced Features
- [ ] Parallel engine execution
- [ ] Advanced conflict resolution
- [ ] Results caching
- [ ] Audit logging
- [ ] Admin dashboard

### Phase 4 - Integrations
- [ ] API endpoints for engine management
- [ ] Webhook events for engine execution
- [ ] Analytics and reporting
- [ ] Machine learning optimization

## FAQ

**Q: Can engines run in parallel?**
A: Currently sequential. Parallel execution is a Phase 3 feature for independent engines.

**Q: What happens if an engine fails?**
A: By default, pipeline continues (`stop_on_error: false`). Failed engine is skipped, others run.

**Q: How are conflicts resolved?**
A: Using configured strategy: maximum discount, minimum, average, first, or last.

**Q: Can I disable an engine?**
A: Yes, set `enabled: false` in config or dynamically via registry.

**Q: How do I test custom engines?**
A: Implement tests using `CalculatingEngineTestCase` base class (see tests/).

---

**Framework Version**: 1.0.0  
**Supported Engines**: 6 (2 implemented, 4 scaffolded)  
**Production Ready**: Yes (Discount, DynamicPricing)  
**Status**: Active Development
