<?php

declare(strict_types=1);

use App\Domains\WorkCore\System\Modules\Finance\Pricing\Http\PricingController;
use Illuminate\Support\Facades\Route;

$middleware = array_values(array_unique(array_merge(
    (array) config('workcore.api.middleware', ['api', 'auth:sanctum', 'workcore.tenant', 'workcore.api']),
    ['workcore.capability:workcore.finance'],
)));
Route::prefix((string) config('workcore.pricing.route_prefix', 'api/v1/workcore/pricing'))
    ->middleware($middleware)->name('api.workcore.pricing.')->group(function (): void {
        Route::post('preview', [PricingController::class, 'preview'])->name('preview');
        Route::post('apply', [PricingController::class, 'apply'])->name('apply');
        Route::put('rules', [PricingController::class, 'upsertRule'])->name('rules.upsert');
        Route::put('seasonal-rates', [PricingController::class, 'upsertSeasonalRate'])->name('seasonal-rates.upsert');
        Route::post('signals', [PricingController::class, 'recordSignal'])->name('signals.record');
        Route::get('analytics', [PricingController::class, 'analytics'])->name('analytics');
    });
