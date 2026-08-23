# WorkCore Dynamic Pricing Engine - Implementation Guide

## Overview

The WorkCore Dynamic Pricing Engine is built on a **Calculating Engine Framework** - an extensible architecture designed to support 20+ calculating engines (dynamic pricing, discounts, promotions, loyalty, tax, etc.). The Dynamic Pricing Engine is the **first pluggable engine** in this framework.

## Architecture

### Core Framework Layers

```
┌─────────────────────────────────────────────────────┐
│  Calculating Engine Framework (Base Architecture)   │
├─────────────────────────────────────────────────────┤
│  - CalculatingEngineContract (Interface)            │
│  - CalculatingEngineRegistry (Factory & Registry)   │
│  - CalculatingEnginePipeline (Orchestrator)         │
│  - CalculatingEngineService (Facade)                │
│  - CalculatingEngineResult (Result wrapper)         │
│  - PricingContext (Shared context through pipeline) │
└─────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────┐
│   Engine Implementations (Pluggable)                │
├─────────────────────────────────────────────────────┤
│  1. DynamicPricingEngine (✓ Implemented)            │
│  2. DiscountEngine (🔄 Planned)                     │
│  3. TaxEngine (🔄 Planned)                          │
│  4-20. [Additional engines] (🔄 Planned)           │
└─────────────────────────────────────────────────────┘
```

## Components

### 1. Contracts (Interfaces)

**Location:** `app/Domains/WorkCore/Calculating/Contracts/`

- `CalculatingEngineContract.php` - Interface for all calculating engines
- `PricingContextContract.php` - Shared context interface
- `CalculatingEngineResultContract.php` - Result interface

### 2. Core Framework Services

**Location:** `app/Domains/WorkCore/Calculating/Services/`

#### CalculatingEngineRegistry
Factory and registry for managing engines with plugin support.

```php
$registry = app(CalculatingEngineRegistry::class);
$registry->registerEngine(new DynamicPricingEngine());
$engine = $registry->getEngine('dynamic_pricing');
```

#### CalculatingEnginePipeline
Orchestrates execution of multiple engines in sequence with:
- Priority-based ordering
- Conflict detection and resolution
- Error handling
- Execution logging

```php
$pipeline = app(CalculatingEnginePipeline::class);
$pipeline->setEngines(['dynamic_pricing', 'discount_engine'])
    ->allowConflicts(false)
    ->setConflictResolution('maximum');
$result = $pipeline->execute($context);
```

#### CalculatingEngineService
Main facade service for the framework.

```php
$service = app(CalculatingEngineService::class);
$result = $service->calculate($context, ['dynamic_pricing', 'tax_engine']);
```

#### PricingContext
Shared context object passed through the pipeline.

```php
$context = new PricingContext('company-1', 100.00, $resource, $data);
$context->set('occupancy', 85)
    ->set('demand_score', 75)
    ->addLogEntry('Processing pricing');
```

### 3. Pricing Domain

**Location:** `app/Domains/WorkCore/Pricing/`

#### Models

- `PricingRule.php` - Configurable pricing rules with conditions and adjustments
- `SeasonalRate.php` - Seasonal pricing multipliers
- `DemandIndicator.php` - Tracks booking/search/inquiry metrics
- `PriceHistory.php` - Audit trail of all price changes
- `OccupancyData.php` - Occupancy snapshots for analytics

#### Engines

- `DynamicPricingEngine.php` - Implements the first calculating engine
  - Applies pricing rules
  - Applies seasonal rates
  - Applies occupancy-based pricing
  - Applies demand-based pricing

#### Algorithms

- `DemandPricingAlgorithm.php` - Demand score calculation and elasticity
- `RevenueManagementAlgorithm.php` - Revenue optimization recommendations

#### Services

- `PricingService.php` - High-level pricing operations facade
- `DemandAnalysisService.php` - Demand analysis, prediction, elasticity
- `RevenueOptimizationService.php` - Revenue optimization & strategy comparison

#### HTTP Controllers

- `PricingRuleController.php` - CRUD for pricing rules
- `DemandAnalysisController.php` - Demand metrics and predictions
- `RevenueAnalyticsController.php` - Revenue reporting and optimization

#### Queue Jobs

- `AnalyzeDemandJob.php` - Hourly/daily demand analysis
- `UpdatePricingJob.php` - Real-time price updates
- `RecordOccupancyJob.php` - Occupancy snapshot recording

#### Configuration

- `config/pricing.php` - Complete pricing configuration

## Database Schema

### Tables

1. **workcore_pricing_rules**
   - Rule conditions and adjustments (JSON)
   - Priority-based execution ordering
   - Time-based activation/expiration

2. **workcore_seasonal_rates**
   - Seasonal multipliers (1.5 = 50% increase)
   - Special date overrides
   - Peak/off-season classification

3. **workcore_demand_indicators**
   - Booking count, search count, inquiry count
   - Demand score (0-100)
   - Demand level classification
   - Booking velocity

4. **workcore_price_history**
   - Original and adjusted prices
   - Adjustment reason and percentage
   - Applied rules and factors
   - Effective date ranges

5. **workcore_occupancy_data**
   - Current occupancy and capacity
   - Occupancy percentage
   - Occupancy level classification
   - 7/30 day forecast

## API Endpoints

### Pricing Rules
```
GET    /api/workcore/pricing/rules                - List rules
POST   /api/workcore/pricing/rules                - Create rule
GET    /api/workcore/pricing/rules/{id}           - Get rule
PUT    /api/workcore/pricing/rules/{id}           - Update rule
DELETE /api/workcore/pricing/rules/{id}           - Delete rule
```

### Demand Analysis
```
GET    /api/workcore/pricing/demand-analysis      - Analyze demand trend
GET    /api/workcore/pricing/demand-elasticity    - Calculate elasticity
GET    /api/workcore/pricing/demand-prediction    - Predict demand
GET    /api/workcore/pricing/demand-metrics       - Current metrics
GET    /api/workcore/pricing/demand-insights      - Get insights
```

### Revenue Analytics
```
GET    /api/workcore/pricing/revenue/optimize     - Get optimal price
GET    /api/workcore/pricing/revenue/suggestions  - Get price suggestions
GET    /api/workcore/pricing/revenue/forecast     - Revenue forecast
GET    /api/workcore/pricing/revenue/analytics    - Revenue analytics
GET    /api/workcore/pricing/revenue/compare      - Compare strategies
GET    /api/workcore/pricing/revenue/report       - Complete report
GET    /api/workcore/pricing/revenue/recommend    - Recommendations
```

## Usage Examples

### 1. Calculate Price with Dynamic Pricing

```php
use App\Domains\WorkCore\Pricing\Services\PricingService;

$pricingService = app(PricingService::class);

$result = $pricingService->calculatePrice(
    companyId: 'company-1',
    basePrice: 100.00,
    resource: ['type' => 'property', 'id' => 1],
    contextData: ['occupancy' => 85, 'demand_score' => 75]
);

// Returns array with final_price, engine_results, applied_engines, etc.
echo "Final Price: " . $result['final_price'];  // e.g., 125.50
```

### 2. Create Pricing Rule

```php
use App\Domains\WorkCore\Pricing\Services\PricingService;

$pricingService = app(PricingService::class);

$rule = $pricingService->createPricingRule(
    companyId: 'company-1',
    data: [
        'name' => 'High Occupancy Premium',
        'rule_type' => 'occupancy',
        'conditions' => json_encode([
            ['field' => 'occupancy', 'operator' => '>', 'value' => 85]
        ]),
        'adjustments' => json_encode([
            ['type' => 'percentage', 'value' => 30]
        ]),
        'priority' => 100,
        'is_active' => true,
    ],
    createdByUserId: $user->id
);
```

### 3. Record Demand Signal

```php
$pricingService->recordDemandSignal(
    companyId: 'company-1',
    resourceType: 'property',
    resourceId: 1,
    signalType: 'booking',  // booking, search, inquiry
    count: 1
);
```

### 4. Analyze Demand

```php
use App\Domains\WorkCore\Pricing\Services\DemandAnalysisService;

$demandService = app(DemandAnalysisService::class);

$analysis = $demandService->analyzeDemand(
    companyId: 'company-1',
    resourceType: 'property',
    resourceId: 1,
    days: 30
);

echo "Demand Level: " . $analysis['demand_level'];
echo "Avg Score: " . $analysis['average_demand_score'];
```

### 5. Optimize Revenue

```php
use App\Domains\WorkCore\Pricing\Services\RevenueOptimizationService;

$revenueService = app(RevenueOptimizationService::class);

$comparison = $revenueService->compareStrategies(
    companyId: 'company-1',
    resourceType: 'property',
    resourceId: 1,
    basePrice: 100.00
);

// Compare current, discount, premium, and dynamic strategies
foreach ($comparison['strategies'] as $strategy => $data) {
    echo "$strategy: $" . $data['estimated_revenue'];
}
```

## Extensibility

### Adding a New Calculating Engine

1. **Create Engine Class**
```php
namespace App\Domains\WorkCore\SomeFeature\Engines;

use App\Domains\WorkCore\Calculating\Contracts\CalculatingEngineContract;
use App\Domains\WorkCore\Calculating\Contracts\PricingContextContract;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineResult;

class YourNewEngine implements CalculatingEngineContract {
    public function getEngineId(): string { return 'your_engine'; }
    public function getEngineName(): string { return 'Your Engine'; }
    // ... implement all methods
}
```

2. **Register in Service Provider**
```php
$service->registerEngine('your_engine', YourNewEngine::class);
```

3. **Use in Pipeline**
```php
$result = $calculatingEngine->calculate($context, ['dynamic_pricing', 'your_engine']);
```

## Testing

### Unit Tests
```bash
php artisan test tests/Unit/Domains/WorkCore/Pricing/
php artisan test tests/Unit/Domains/WorkCore/Calculating/
```

### Feature Tests
```bash
php artisan test tests/Feature/Domains/WorkCore/Pricing/
```

### Test Coverage
```bash
php artisan test --coverage tests/Unit/Domains/WorkCore/
```

## Configuration

Edit `config/pricing.php` or set environment variables:

```env
PRICING_ENABLE_RULES=true
PRICING_ENABLE_SEASONAL=true
PRICING_ENABLE_OCCUPANCY=true
PRICING_ENABLE_DEMAND=true
PRICING_MIN_PRICE=10
PRICING_MAX_PRICE=500
PRICING_RESOURCE_TYPES=property,hotel,apartment
```

## Performance Considerations

1. **Price Caching** - Cache calculated prices with occupancy/demand keys
2. **Batch Processing** - Use queue jobs for bulk calculations
3. **Indexes** - Queries use indexed columns (company_id, resource_type, resource_id)
4. **Lazy Loading** - Demand/occupancy data loaded on-demand

## Monitoring & Logging

All calculations are logged with:
- Applied engines
- Execution time
- Price history
- Error messages (if any)
- Conflict resolutions

Access logs via:
```php
$context->getExecutionLog();
$context->getPriceHistory();
```

## Future Roadmap

### Phase 2: Additional Engines
- Discount Engine (percentage, fixed, BOGO)
- Promotion Engine (seasonal campaigns)
- Loyalty Engine (points, rewards, tiers)
- Tax Engine (region-based, category-based)

### Phase 3: Advanced Features
- Machine learning price predictions
- Competitor price monitoring
- A/B testing framework
- Price elasticity optimization

## Migration from Standalone Implementation

If you had a standalone pricing system, migrate to this framework by:
1. Extracting business logic into an Engine class
2. Registering it in the CalculatingEngineRegistry
3. Updating calls to use CalculatingEngineService
4. Adding conflict resolution rules

## Support & Troubleshooting

### Prices Not Updating
- Check if pricing rules are active and conditions match context
- Verify occupancy/demand data is being recorded
- Check logs: `context->getExecutionLog()`

### Unexpected Price Conflicts
- Review conflict resolution strategy in config
- Check engine priorities
- Enable detailed logging: `PRICING_AUDIT_LOG_ALL=true`

### Performance Issues
- Index heavy columns in database
- Use queue jobs for analysis
- Cache context for repeated calculations
