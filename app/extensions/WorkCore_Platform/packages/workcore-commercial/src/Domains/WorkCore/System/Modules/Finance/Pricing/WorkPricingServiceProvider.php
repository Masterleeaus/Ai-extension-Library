<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing;

use App\Domains\WorkCore\System\Actions\{ActionDefinition,BusinessActionRegistry};
use App\Domains\WorkCore\System\Modules\Finance\Pricing\Actions\{ApplyDynamicPrice,GetPricingAnalytics,PreviewDynamicPrice,RecordPricingSignal,UpsertPricingRule,UpsertSeasonalRate};
use App\Domains\WorkCore\System\Modules\Finance\Pricing\Contracts\PricingRepositoryContract;
use App\Domains\WorkCore\System\Modules\Finance\Pricing\Domain\{DynamicPriceCalculator,PricingInputValidator};
use App\Domains\WorkCore\System\Modules\Finance\Pricing\Infrastructure\DatabasePricingRepository;
use App\Domains\WorkCore\System\Modules\Finance\Pricing\Services\{DemandAnalysisService,PricingService,RevenueOptimizationService};
use App\Domains\WorkCore\System\ReadModels\{ReadModelDefinition,ReadModelRegistry};
use Illuminate\Support\ServiceProvider;

final class WorkPricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/pricing.php', 'workcore.pricing');
        if (! (bool) config('workcore.pricing.enabled', true)) {
            return;
        }
        $this->app->bind(PricingRepositoryContract::class, DatabasePricingRepository::class);
        $this->app->singleton(PricingInputValidator::class);
        $this->app->singleton(DynamicPriceCalculator::class);
        $this->app->singleton(DemandAnalysisService::class);
        $this->app->singleton(RevenueOptimizationService::class);
        $this->app->singleton(PricingService::class);

        $actions = $this->app->make(BusinessActionRegistry::class);
        $actions->register(new ActionDefinition('workcore.pricing.rule.upsert', UpsertPricingRule::class, 'high', true, 'workcore.finance', (string) config('workcore.pricing.permissions.manage_rules')));
        $actions->register(new ActionDefinition('workcore.pricing.seasonal.upsert', UpsertSeasonalRate::class, 'high', true, 'workcore.finance', (string) config('workcore.pricing.permissions.manage_rules')));
        $actions->register(new ActionDefinition('workcore.pricing.signal.record', RecordPricingSignal::class, 'medium', false, 'workcore.finance', (string) config('workcore.pricing.permissions.record_signals')));
        $actions->register(new ActionDefinition('workcore.pricing.apply', ApplyDynamicPrice::class, 'high', true, 'workcore.finance', (string) config('workcore.pricing.permissions.apply')));

        $reads = $this->app->make(ReadModelRegistry::class);
        $reads->register(new ReadModelDefinition('workcore.pricing.preview', PreviewDynamicPrice::class, 'workcore.finance', permission: (string) config('workcore.pricing.permissions.view')));
        $reads->register(new ReadModelDefinition('workcore.pricing.analytics', GetPricingAnalytics::class, 'workcore.finance', permission: (string) config('workcore.pricing.permissions.analytics')));
    }

    public function boot(): void
    {
        if (! (bool) config('workcore.pricing.enabled', true)) {
            return;
        }
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'workcore-pricing');
        if ((bool) config('workcore.pricing.routes_enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        }
    }
}
