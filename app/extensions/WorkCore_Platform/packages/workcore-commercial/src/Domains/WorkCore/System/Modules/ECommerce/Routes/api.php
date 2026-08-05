<?php

use Illuminate\Support\Facades\Route;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Controllers\CartController;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Controllers\CheckoutController;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Controllers\OrderController;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Controllers\ReviewController;

Route::prefix('api/workcore/ecommerce')->group(function () {
    // Shopping Cart endpoints
    Route::post('/carts', [CartController::class, 'store']);
    Route::get('/carts/{id}', [CartController::class, 'show']);
    Route::post('/carts/{id}/items', [CartController::class, 'addItem']);
    Route::delete('/carts/{id}/items/{itemId}', [CartController::class, 'removeItem']);
    Route::put('/carts/{id}/items/{itemId}', [CartController::class, 'updateQuantity']);
    Route::post('/carts/{id}/coupon', [CartController::class, 'applyCoupon']);
    Route::delete('/carts/{id}/coupon', [CartController::class, 'removeCoupon']);

    // Checkout endpoints
    Route::post('/checkout', [CheckoutController::class, 'process']);
    Route::get('/checkout/summary', [CheckoutController::class, 'summary']);

    // Order endpoints
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::post('/orders/{id}/refund', [OrderController::class, 'refund']);
    Route::get('/orders/{id}/export', [OrderController::class, 'export']);

    // Review endpoints
    Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);
    Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
    Route::post('/reviews/{id}/approve', [ReviewController::class, 'approve']);
    Route::post('/reviews/{id}/reject', [ReviewController::class, 'reject']);
});
