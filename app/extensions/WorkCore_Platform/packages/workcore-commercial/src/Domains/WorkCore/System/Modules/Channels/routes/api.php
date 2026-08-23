<?php

use App\Domains\WorkCore\System\Modules\Channels\Http\Controllers\ChannelController;
use App\Domains\WorkCore\System\Modules\Channels\Http\Controllers\ChannelInventoryController;
use App\Domains\WorkCore\System\Modules\Channels\Http\Controllers\ChannelOrderController;
use App\Domains\WorkCore\System\Modules\Channels\Http\Controllers\ChannelPricingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'tenant'])->group(function () {
    Route::prefix('api/workcore/channels')->group(function () {
        // Channel Management
        Route::post('/', [ChannelController::class, 'register'])->name('channels.register');
        Route::get('/', [ChannelController::class, 'index'])->name('channels.index');
        Route::get('/{id}', [ChannelController::class, 'show'])->name('channels.show');
        Route::put('/{id}', [ChannelController::class, 'update'])->name('channels.update');
        Route::delete('/{id}', [ChannelController::class, 'destroy'])->name('channels.destroy');
        Route::post('/{id}/test', [ChannelController::class, 'test'])->name('channels.test');
        Route::get('/{id}/status', [ChannelController::class, 'status'])->name('channels.status');
        Route::post('/{id}/sync', [ChannelController::class, 'sync'])->name('channels.sync');

        // Channel Inventory
        Route::prefix('{channelId}/inventory')->group(function () {
            Route::get('/', [ChannelInventoryController::class, 'index'])->name('channels.inventory.index');
            Route::get('/{productId}', [ChannelInventoryController::class, 'show'])->name('channels.inventory.show');
            Route::post('/sync', [ChannelInventoryController::class, 'sync'])->name('channels.inventory.sync');
            Route::post('/reserve', [ChannelInventoryController::class, 'reserve'])->name('channels.inventory.reserve');
            Route::post('/release', [ChannelInventoryController::class, 'release'])->name('channels.inventory.release');
        });

        // Channel Orders
        Route::prefix('{channelId}/orders')->group(function () {
            Route::get('/', [ChannelOrderController::class, 'index'])->name('channels.orders.index');
            Route::get('/{orderId}', [ChannelOrderController::class, 'show'])->name('channels.orders.show');
            Route::post('/sync', [ChannelOrderController::class, 'sync'])->name('channels.orders.sync');
            Route::post('/{orderId}/push-status', [ChannelOrderController::class, 'pushStatus'])->name('channels.orders.push-status');
        });

        // Channel Pricing
        Route::prefix('{channelId}/pricing')->group(function () {
            Route::get('/', [ChannelPricingController::class, 'index'])->name('channels.pricing.index');
            Route::get('/{productId}', [ChannelPricingController::class, 'show'])->name('channels.pricing.show');
            Route::post('/sync', [ChannelPricingController::class, 'sync'])->name('channels.pricing.sync');
            Route::get('/anomalies', [ChannelPricingController::class, 'anomalies'])->name('channels.pricing.anomalies');
        });

        // Mappings
        Route::get('/mappings', [ChannelController::class, 'mappings'])->name('channels.mappings');
    });
});
