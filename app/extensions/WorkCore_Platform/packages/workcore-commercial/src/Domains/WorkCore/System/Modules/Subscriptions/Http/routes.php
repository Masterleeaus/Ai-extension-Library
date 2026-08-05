<?php

use Illuminate\Support\Facades\Route;
use WorkCore\Subscriptions\Http\Controllers\AnalyticsController;
use WorkCore\Subscriptions\Http\Controllers\BillingController;
use WorkCore\Subscriptions\Http\Controllers\MembershipTierController;
use WorkCore\Subscriptions\Http\Controllers\SubscriptionController;
use WorkCore\Subscriptions\Http\Controllers\UsageController;

Route::prefix('api/workcore/subscriptions')
    ->name('subscriptions.')
    ->group(function () {
        // Membership Tier Routes
        Route::post('/tiers', [MembershipTierController::class, 'store'])->name('tiers.store');
        Route::get('/tiers', [MembershipTierController::class, 'index'])->name('tiers.index');
        Route::get('/tiers/{tier_id}', [MembershipTierController::class, 'show'])->name('tiers.show');
        Route::put('/tiers/{tier_id}', [MembershipTierController::class, 'update'])->name('tiers.update');
        Route::delete('/tiers/{tier_id}', [MembershipTierController::class, 'destroy'])->name('tiers.destroy');

        // Subscription Routes
        Route::post('/', [SubscriptionController::class, 'store'])->name('store');
        Route::get('/{subscription_id}', [SubscriptionController::class, 'show'])->name('show');
        Route::get('/customer/subscriptions', [SubscriptionController::class, 'getCustomerSubscriptions'])->name('customer.subscriptions');
        Route::put('/{subscription_id}/upgrade', [SubscriptionController::class, 'upgrade'])->name('upgrade');
        Route::put('/{subscription_id}/downgrade', [SubscriptionController::class, 'downgrade'])->name('downgrade');
        Route::put('/{subscription_id}/pause', [SubscriptionController::class, 'pause'])->name('pause');
        Route::put('/{subscription_id}/resume', [SubscriptionController::class, 'resume'])->name('resume');
        Route::post('/{subscription_id}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::get('/{subscription_id}/status', [SubscriptionController::class, 'status'])->name('status');

        // Usage Limit Routes
        Route::get('/{subscription_id}/usage', [UsageController::class, 'show'])->name('usage.show');
        Route::get('/{subscription_id}/usage/{feature_slug}', [UsageController::class, 'getFeatureUsage'])->name('usage.feature');
        Route::post('/{subscription_id}/usage/{feature_slug}/increment', [UsageController::class, 'increment'])->name('usage.increment');

        // Billing Routes
        Route::get('/{subscription_id}/billing/history', [BillingController::class, 'history'])->name('billing.history');
        Route::get('/{subscription_id}/invoices', [BillingController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{invoice_id}', [BillingController::class, 'getInvoice'])->name('invoice.show');
        Route::get('/{subscription_id}/invoices/unpaid', [BillingController::class, 'unpaidInvoices'])->name('invoices.unpaid');
        Route::post('/invoices/{invoice_id}/mark-paid', [BillingController::class, 'markInvoiceAsPaid'])->name('invoice.mark-paid');
        Route::post('/{subscription_id}/payment/retry', [BillingController::class, 'retryPayment'])->name('payment.retry');

        // Analytics Routes
        Route::get('/analytics/mrr', [AnalyticsController::class, 'getMRR'])->name('analytics.mrr');
        Route::get('/analytics/churn', [AnalyticsController::class, 'getChurn'])->name('analytics.churn');
        Route::get('/analytics/clv', [AnalyticsController::class, 'getCLV'])->name('analytics.clv');
        Route::get('/analytics/cohort', [AnalyticsController::class, 'getCohortAnalysis'])->name('analytics.cohort');
        Route::get('/analytics/growth', [AnalyticsController::class, 'getGrowth'])->name('analytics.growth');
        Route::get('/analytics/revenue', [AnalyticsController::class, 'getRevenue'])->name('analytics.revenue');
        Route::get('/analytics/tier-distribution', [AnalyticsController::class, 'getTierDistribution'])->name('analytics.tier-distribution');
        Route::get('/analytics/top-tiers', [AnalyticsController::class, 'getTopTiers'])->name('analytics.top-tiers');
    });
