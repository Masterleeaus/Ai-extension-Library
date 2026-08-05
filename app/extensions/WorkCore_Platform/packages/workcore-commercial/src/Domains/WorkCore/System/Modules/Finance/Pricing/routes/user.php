<?php

declare(strict_types=1);

use App\Domains\WorkCore\System\Modules\Finance\Pricing\Http\PricingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'workcore.tenant', 'workcore.workspace-capability:workcore.finance'])
    ->prefix('dashboard/user/workcore/commercial/pricing')->group(function (): void {
        Route::get('/', [PricingController::class, 'dashboard'])
            ->name('dashboard.user.workcore.commercial.pricing');
    });
