<?php

declare(strict_types=1);

namespace App\Extensions\ChatbotEcommerce\System;

use App\Domains\Marketplace\Contracts\UninstallExtensionServiceProviderInterface;
use App\Extensions\Chatbot\System\Http\Middleware\LanguageMiddleware;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\BnplApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\ChatbotEcommerceApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\CheckoutApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\CommerceRoleApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\CommerceSessionAuthorityApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\ConversationalCommerceApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\CustomerCommunicationAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\CustomerCommunicationApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\FulfillmentApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\MarketplaceBulkAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\InventoryApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\MarketplaceAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\MarketplaceApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\MarketplaceInventoryAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\MarketplaceWriteAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\ListingIntelligenceAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\NativeCommerceApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\NativeOrderApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\PaymentApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\PaymentWebhookController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\CommerceCredentialAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\BnplAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\PaymentAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\PricingAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\RentalHireAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\RentalHireApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\ShippingAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\ShippingApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\TaxAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api\UnifiedOrderWorkbenchAdminApiController;
use App\Extensions\ChatbotEcommerce\System\Http\Middleware\RequireCommerceSessionAuthority;
use App\Extensions\ChatbotEcommerce\System\Http\Controllers\ChatbotEcommerceController;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class ChatbotEcommerceServiceProvider extends ServiceProvider implements UninstallExtensionServiceProviderInterface
{
    public function boot(Kernel $kernel): void
    {
        $this->registerTranslations()
            ->registerViews()
            ->registerRoutes()
            ->registerMigrations();
    }

    protected function registerTranslations(): static
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'chatbot-ecommerce');

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'chatbot-ecommerce');

        return $this;
    }

    public function registerMigrations(): static
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        return $this;
    }

    private function registerRoutes(): static
    {
        $router = $this->app['router'];

        $router->middleware(['web', 'auth'])->prefix('dashboard/chatbot-ecommerce')->name('dashboard.chatbot-ecommerce.')
            ->group(function (Router $router): void {
                $router->get('', [ChatbotEcommerceController::class, 'index'])->name('index');
            });

        $router->middleware(['api', LanguageMiddleware::class])->prefix('api/v2/chatbot')->name('api.v2.chatbot.')
            ->group(function (Router $router): void {
                $router->post('{chatbot:uuid}/session/{sessionId}/productAddToCart', [ChatbotEcommerceApiController::class, 'productAddToCart'])->name('product.addToCart');
                $router->post('{chatbot:uuid}/session/{sessionId}/productUpdateQuantity', [ChatbotEcommerceApiController::class, 'productUpdateQuantity'])->name('product.UpdateQuantity');
                $router->post('{chatbot:uuid}/session/{sessionId}/productCartCheckout', [ChatbotEcommerceApiController::class, 'productCartCheckout'])->name('product.cartCheckout');
                $router->post('{chatbot:uuid}/session/{sessionId}/productGetCart', [ChatbotEcommerceApiController::class, 'productGetCart'])->name('product.getCart');
            });

        $router->middleware(['api', LanguageMiddleware::class])->prefix('api/v3/chatbot/ecommerce')->name('api.v3.chatbot.ecommerce.')
            ->group(function (Router $router): void {
                $router->post('{chatbot:uuid}/commerce/session-authority', [CommerceSessionAuthorityApiController::class, 'issue'])->name('commerce.session-authority.issue');
                $router->get('commerce/roles', [CommerceRoleApiController::class, 'definitions'])->name('commerce.roles');
                $router->post('commerce/roles/resolve', [CommerceRoleApiController::class, 'resolve'])->name('commerce.roles.resolve');
                $router->get('commerce/tools', [ConversationalCommerceApiController::class, 'definitions'])->name('commerce.tools');
            });

        $router->middleware(['api', LanguageMiddleware::class, RequireCommerceSessionAuthority::class])->prefix('api/v3/chatbot/ecommerce')->name('api.v3.chatbot.ecommerce.')
            ->group(function (Router $router): void {
                $session = '{chatbot:uuid}/session/{sessionId}';

                $router->get($session . '/context', [ConversationalCommerceApiController::class, 'context'])->name('context.show');
                $router->put($session . '/context', [ConversationalCommerceApiController::class, 'updateContext'])->name('context.update');
                $router->post($session . '/tools/execute', [ConversationalCommerceApiController::class, 'execute'])->name('tools.execute');
                $router->post($session . '/actions/{actionUuid}/execute', [ConversationalCommerceApiController::class, 'executeApproved'])->name('actions.execute');
                $router->post($session . '/actions/{journal}/rollback', [ConversationalCommerceApiController::class, 'rollback'])->name('actions.rollback');
                $router->put('{chatbot:uuid}/budget-locks', [ConversationalCommerceApiController::class, 'putBudget'])->name('budget-locks.update');

                $router->post($session . '/marketplace/searches', [MarketplaceApiController::class, 'store'])->name('marketplace.searches.store');
                $router->get($session . '/marketplace/searches/{search}', [MarketplaceApiController::class, 'show'])->name('marketplace.searches.show');
                $router->get($session . '/marketplace/{provider}/listings/{externalListingId}', [MarketplaceApiController::class, 'listing'])->name('marketplace.listings.show');

                $router->get($session . '/products', [NativeCommerceApiController::class, 'products'])->name('products.index');
                $router->get($session . '/products/{product}', [NativeCommerceApiController::class, 'product'])->name('products.show');
                $router->get($session . '/cart', [NativeCommerceApiController::class, 'cart'])->name('cart.show');
                $router->post($session . '/cart/lines', [NativeCommerceApiController::class, 'addLine'])->name('cart.lines.store');
                $router->put($session . '/cart/lines/{line}', [NativeCommerceApiController::class, 'putLine'])->name('cart.lines.update');
                $router->patch($session . '/cart/lines/{line}', [NativeCommerceApiController::class, 'updateLine'])->name('cart.lines.patch');
                $router->delete($session . '/cart/lines/{line}', [NativeCommerceApiController::class, 'removeLine'])->name('cart.lines.destroy');
                $router->delete($session . '/cart', [NativeCommerceApiController::class, 'clear'])->name('cart.clear');
                $router->post($session . '/cart/recalculate', [NativeCommerceApiController::class, 'recalculate'])->name('cart.recalculate');
                $router->post($session . '/cart/coupon', [NativeCommerceApiController::class, 'coupon'])->name('cart.coupon');
                $router->delete($session . '/cart/coupon', [NativeCommerceApiController::class, 'removeCoupon'])->name('cart.coupon.destroy');
                $router->post($session . '/cart/merge', [NativeCommerceApiController::class, 'merge'])->name('cart.merge');
                $router->post($session . '/cart/abandon', [NativeCommerceApiController::class, 'abandon'])->name('cart.abandon');
                $router->post($session . '/cart/recover', [NativeCommerceApiController::class, 'recover'])->name('cart.recover');

                $router->get($session . '/checkout', [CheckoutApiController::class, 'show'])->name('checkout.show');
                $router->put($session . '/checkout/customer', [CheckoutApiController::class, 'customer'])->name('checkout.customer.update');
                $router->put($session . '/checkout/delivery', [CheckoutApiController::class, 'delivery'])->name('checkout.delivery.update');
                $router->post($session . '/checkout/prepare', [CheckoutApiController::class, 'prepare'])->name('checkout.prepare');
                $router->post($session . '/checkout/approve', [CheckoutApiController::class, 'approve'])->name('checkout.approve');
                $router->post($session . '/checkout/cancel', [CheckoutApiController::class, 'cancel'])->name('checkout.cancel');

                $router->get($session . '/orders', [NativeOrderApiController::class, 'index'])->name('orders.index');
                $router->get($session . '/orders/{order}', [NativeOrderApiController::class, 'show'])->name('orders.show');
                $router->post($session . '/checkouts/{checkout}/orders', [NativeOrderApiController::class, 'materialize'])->name('orders.materialize');
                $router->post($session . '/orders/{order}/returns', [NativeOrderApiController::class, 'requestReturn'])->name('returns.store');
                $router->post('{chatbot:uuid}/orders/{order}/transition', [NativeOrderApiController::class, 'transition'])->name('orders.transition');
                $router->post('{chatbot:uuid}/returns/{return}/transition', [NativeOrderApiController::class, 'transitionReturn'])->name('returns.transition');
                $router->post('{chatbot:uuid}/returns/{return}/refund', [NativeOrderApiController::class, 'refundReturn'])->name('returns.refund');

                $router->get($session . '/communications/tools', [CustomerCommunicationApiController::class, 'tools'])->name('support.tools');
                $router->post($session . '/support/messages', [CustomerCommunicationApiController::class, 'ingest'])->name('support.messages');
                $router->get($session . '/support/threads/{thread}', [CustomerCommunicationApiController::class, 'context'])->name('support.threads.show');
                $router->post($session . '/support/threads/{thread}/drafts', [CustomerCommunicationApiController::class, 'draft'])->name('support.drafts.store');
                $router->post($session . '/support/threads/{thread}/actions', [CustomerCommunicationApiController::class, 'prepareAction'])->name('support.actions.store');
                $router->post($session . '/support/threads/{thread}/actions/{actionUuid}/execute', [CustomerCommunicationApiController::class, 'executeAction'])->name('support.actions.execute');
                $router->post($session . '/support/threads/{thread}/escalations', [CustomerCommunicationApiController::class, 'escalate'])->name('support.escalations.store');

                $router->get($session . '/shipping/options', [ShippingApiController::class, 'options'])->name('shipping.options');
                $router->get($session . '/inventory/{variant}/availability', [InventoryApiController::class, 'show'])->name('inventory.availability');
                $router->post($session . '/inventory/reservations', [InventoryApiController::class, 'reserve'])->name('inventory.reservations.store');
                $router->delete($session . '/inventory/reservations/{reservation}', [InventoryApiController::class, 'release'])->name('inventory.reservations.release');

                $router->post($session . '/checkout/payments/intents', [PaymentApiController::class, 'createCheckout'])->name('checkout.payments.intents.store');
                $router->get($session . '/checkout/payments/intents/{intent}', [PaymentApiController::class, 'showCheckout'])->name('checkout.payments.intents.show');
                $router->post('{chatbot:uuid}/rentals/accounts/{account}/payments/{payment}/intents', [PaymentApiController::class, 'createRental'])->name('rentals.payments.intents.store');
                $router->get('{chatbot:uuid}/rentals/accounts/{account}/payments/{payment}/intents/{intent}', [PaymentApiController::class, 'showRental'])->name('rentals.payments.intents.show');
                $router->post($session . '/bnpl/checkout/offers', [BnplApiController::class, 'checkoutOffers'])->name('bnpl.checkout.offers');
                $router->post($session . '/bnpl/checkout/select', [BnplApiController::class, 'selectCheckout'])->name('bnpl.checkout.select');
                $router->post('{chatbot:uuid}/rentals/accounts/{account}/payments/{payment}/bnpl/offers', [BnplApiController::class, 'rentalOffers'])->name('bnpl.rentals.offers');
                $router->post('{chatbot:uuid}/rentals/accounts/{account}/payments/{payment}/bnpl/select', [BnplApiController::class, 'selectRental'])->name('bnpl.rentals.select');
                $router->post($session . '/payments/intents', [PaymentApiController::class, 'createCheckout'])->name('payments.intents.store');
                $router->get($session . '/payments/intents/{intent}', [PaymentApiController::class, 'showCheckout'])->name('payments.intents.show');

                $router->get('{chatbot:uuid}/rentals/accounts/{account}', [RentalHireApiController::class, 'summary'])->name('rentals.accounts.show');
                $router->get('{chatbot:uuid}/rentals/accounts/{account}/ledger', [RentalHireApiController::class, 'ledger'])->name('rentals.accounts.ledger');
                $router->post('{chatbot:uuid}/rentals/accounts/{account}/payment-requests', [RentalHireApiController::class, 'paymentRequest'])->name('rentals.payment-requests');
                $router->get('{chatbot:uuid}/rentals/accounts/{account}/receipts/{receipt}', [RentalHireApiController::class, 'receipt'])->name('rentals.receipts.show');
            });

        $router->middleware(['api', 'auth'])->prefix('api/v3/chatbot/ecommerce')->name('api.v3.chatbot.ecommerce.admin.')
            ->group(function (Router $router): void {
                $base = '{chatbot:uuid}';
                $router->get($base . '/support/threads', [CustomerCommunicationAdminApiController::class, 'threads'])->name('support.threads.index');
                $router->get($base . '/support/threads/{thread}', [CustomerCommunicationAdminApiController::class, 'show'])->name('support.threads.show');
                $router->put($base . '/support/threads/{thread}', [CustomerCommunicationAdminApiController::class, 'updateThread'])->name('support.threads.update');
                $router->get($base . '/support/policies', [CustomerCommunicationAdminApiController::class, 'policy'])->name('support.policies.show');
                $router->put($base . '/support/policies', [CustomerCommunicationAdminApiController::class, 'updatePolicy'])->name('support.policies.update');
                $router->post($base . '/support/escalations/{escalation}/resolve', [CustomerCommunicationAdminApiController::class, 'resolveEscalation'])->name('support.escalations.resolve');
                $router->get($base . '/marketplaces', [MarketplaceAdminApiController::class, 'connections'])->name('marketplaces.index');
                $router->post($base . '/marketplaces', [MarketplaceAdminApiController::class, 'storeConnection'])->name('marketplaces.store');
                $router->put($base . '/marketplaces/{connection}', [MarketplaceAdminApiController::class, 'updateConnection'])->name('marketplaces.update');
                $router->post($base . '/marketplaces/{connection}/orders/import', [MarketplaceAdminApiController::class, 'importOrders'])->name('marketplaces.orders.import');
                $router->get($base . '/marketplaces/orders', [MarketplaceAdminApiController::class, 'orders'])->name('marketplaces.orders.index');
                $router->get($base . '/marketplaces/sync-runs', [MarketplaceAdminApiController::class, 'syncRuns'])->name('marketplaces.sync-runs.index');

                $router->get($base . '/unified-orders', [UnifiedOrderWorkbenchAdminApiController::class, 'index'])->name('unified-orders.index');
                $router->get($base . '/unified-orders/{unifiedOrder}', [UnifiedOrderWorkbenchAdminApiController::class, 'show'])->name('unified-orders.show');
                $router->post($base . '/unified-orders/sync', [UnifiedOrderWorkbenchAdminApiController::class, 'sync'])->name('unified-orders.sync');
                $router->post($base . '/unified-orders/import-external', [UnifiedOrderWorkbenchAdminApiController::class, 'importExternal'])->name('unified-orders.import-external');
                $router->post($base . '/unified-orders/{unifiedOrder}/settlements/import', [UnifiedOrderWorkbenchAdminApiController::class, 'importSettlements'])->name('unified-orders.settlements.import');
                $router->post($base . '/unified-orders/{unifiedOrder}/reconcile', [UnifiedOrderWorkbenchAdminApiController::class, 'reconcile'])->name('unified-orders.reconcile');
                $router->get($base . '/order-exceptions', [UnifiedOrderWorkbenchAdminApiController::class, 'exceptions'])->name('order-exceptions.index');
                $router->get($base . '/order-exceptions/{exception}', [UnifiedOrderWorkbenchAdminApiController::class, 'showException'])->name('order-exceptions.show');
                $router->post($base . '/order-exceptions/{exception}/acknowledge', [UnifiedOrderWorkbenchAdminApiController::class, 'acknowledge'])->name('order-exceptions.acknowledge');
                $router->post($base . '/order-exceptions/{exception}/assign', [UnifiedOrderWorkbenchAdminApiController::class, 'assign'])->name('order-exceptions.assign');
                $router->post($base . '/order-exceptions/{exception}/resolve', [UnifiedOrderWorkbenchAdminApiController::class, 'resolve'])->name('order-exceptions.resolve');
                $router->post($base . '/unified-orders/{unifiedOrder}/refund-proposals', [UnifiedOrderWorkbenchAdminApiController::class, 'prepareRefund'])->name('unified-orders.refunds.prepare');
                $router->post($base . '/unified-orders/{unifiedOrder}/customer-contact-proposals', [UnifiedOrderWorkbenchAdminApiController::class, 'prepareCustomerContact'])->name('unified-orders.customer-contact.prepare');
                $router->post($base . '/unified-orders/{unifiedOrder}/communications/{thread}/link', [UnifiedOrderWorkbenchAdminApiController::class, 'linkThread'])->name('unified-orders.communications.link');

                $router->get($base . '/marketplace-write-proposals', [MarketplaceWriteAdminApiController::class, 'index'])->name('marketplace-write-proposals.index');
                $router->get($base . '/marketplace-write-proposals/{proposal}', [MarketplaceWriteAdminApiController::class, 'show'])->name('marketplace-write-proposals.show');
                $router->post($base . '/marketplace-write-proposals', [MarketplaceWriteAdminApiController::class, 'prepare'])->name('marketplace-write-proposals.store');
                $router->post($base . '/marketplace-write-proposals/{proposal}/approve', [MarketplaceWriteAdminApiController::class, 'approve'])->name('marketplace-write-proposals.approve');
                $router->post($base . '/marketplace-write-proposals/{proposal}/execute', [MarketplaceWriteAdminApiController::class, 'execute'])->name('marketplace-write-proposals.execute');
                $router->post($base . '/marketplace-write-proposals/{proposal}/rollback', [MarketplaceWriteAdminApiController::class, 'rollback'])->name('marketplace-write-proposals.rollback');

                $router->get($base . '/marketplace-bulk-batches', [MarketplaceBulkAdminApiController::class, 'index'])->name('marketplace-bulk-batches.index');
                $router->get($base . '/marketplace-bulk-batches/{batch}', [MarketplaceBulkAdminApiController::class, 'show'])->name('marketplace-bulk-batches.show');
                $router->post($base . '/marketplaces/{connection}/bulk-batches/preview', [MarketplaceBulkAdminApiController::class, 'preview'])->name('marketplace-bulk-batches.preview');
                $router->post($base . '/marketplace-bulk-batches/{batch}/approve', [MarketplaceBulkAdminApiController::class, 'approve'])->name('marketplace-bulk-batches.approve');
                $router->post($base . '/marketplace-bulk-batches/{batch}/execute', [MarketplaceBulkAdminApiController::class, 'execute'])->name('marketplace-bulk-batches.execute');
                $router->post($base . '/marketplace-bulk-batches/{batch}/rollback', [MarketplaceBulkAdminApiController::class, 'rollback'])->name('marketplace-bulk-batches.rollback');

                $router->get($base . '/marketplace-inventory/policies', [MarketplaceInventoryAdminApiController::class, 'policies'])->name('marketplace-inventory.policies.index');
                $router->post($base . '/marketplace-inventory/policies', [MarketplaceInventoryAdminApiController::class, 'storePolicy'])->name('marketplace-inventory.policies.store');
                $router->put($base . '/marketplace-inventory/policies/{policy}', [MarketplaceInventoryAdminApiController::class, 'updatePolicy'])->name('marketplace-inventory.policies.update');
                $router->get($base . '/marketplace-inventory/mappings', [MarketplaceInventoryAdminApiController::class, 'mappings'])->name('marketplace-inventory.mappings.index');
                $router->post($base . '/marketplace-inventory/mappings', [MarketplaceInventoryAdminApiController::class, 'storeMapping'])->name('marketplace-inventory.mappings.store');
                $router->put($base . '/marketplace-inventory/mappings/{mapping}', [MarketplaceInventoryAdminApiController::class, 'updateMapping'])->name('marketplace-inventory.mappings.update');
                $router->get($base . '/marketplace-inventory/scans', [MarketplaceInventoryAdminApiController::class, 'scans'])->name('marketplace-inventory.scans.index');
                $router->post($base . '/marketplace-inventory/connections/{connection}/scan', [MarketplaceInventoryAdminApiController::class, 'scan'])->name('marketplace-inventory.scan');
                $router->get($base . '/marketplace-inventory/conflicts', [MarketplaceInventoryAdminApiController::class, 'conflicts'])->name('marketplace-inventory.conflicts.index');
                $router->get($base . '/marketplace-inventory/conflicts/{conflict}', [MarketplaceInventoryAdminApiController::class, 'showConflict'])->name('marketplace-inventory.conflicts.show');
                $router->post($base . '/marketplace-inventory/conflicts/{conflict}/acknowledge', [MarketplaceInventoryAdminApiController::class, 'acknowledge'])->name('marketplace-inventory.conflicts.acknowledge');
                $router->post($base . '/marketplace-inventory/conflicts/{conflict}/ignore', [MarketplaceInventoryAdminApiController::class, 'ignore'])->name('marketplace-inventory.conflicts.ignore');
                $router->post($base . '/marketplace-inventory/conflicts/{conflict}/corrections', [MarketplaceInventoryAdminApiController::class, 'prepareCorrection'])->name('marketplace-inventory.conflicts.corrections');
                $router->post($base . '/marketplace-inventory/conflicts/{conflict}/refresh', [MarketplaceInventoryAdminApiController::class, 'refreshConflict'])->name('marketplace-inventory.conflicts.refresh');

                $router->get($base . '/listing-intelligence/brand-voices', [ListingIntelligenceAdminApiController::class, 'brandVoices'])->name('listing-intelligence.brand-voices.index');
                $router->post($base . '/listing-intelligence/brand-voices', [ListingIntelligenceAdminApiController::class, 'storeBrandVoice'])->name('listing-intelligence.brand-voices.store');
                $router->put($base . '/listing-intelligence/brand-voices/{brandVoice}', [ListingIntelligenceAdminApiController::class, 'updateBrandVoice'])->name('listing-intelligence.brand-voices.update');
                $router->get($base . '/listing-intelligence/product-profiles', [ListingIntelligenceAdminApiController::class, 'productProfiles'])->name('listing-intelligence.product-profiles.index');
                $router->post($base . '/listing-intelligence/product-profiles', [ListingIntelligenceAdminApiController::class, 'storeProductProfile'])->name('listing-intelligence.product-profiles.store');
                $router->put($base . '/listing-intelligence/product-profiles/{productProfile}', [ListingIntelligenceAdminApiController::class, 'updateProductProfile'])->name('listing-intelligence.product-profiles.update');
                $router->get($base . '/listing-intelligence/compliance-rules', [ListingIntelligenceAdminApiController::class, 'rules'])->name('listing-intelligence.compliance-rules.index');
                $router->post($base . '/listing-intelligence/compliance-rules', [ListingIntelligenceAdminApiController::class, 'storeRule'])->name('listing-intelligence.compliance-rules.store');
                $router->get($base . '/listing-intelligence/runs', [ListingIntelligenceAdminApiController::class, 'runs'])->name('listing-intelligence.runs.index');
                $router->get($base . '/listing-intelligence/runs/{run}', [ListingIntelligenceAdminApiController::class, 'show'])->name('listing-intelligence.runs.show');
                $router->post($base . '/marketplaces/{connection}/listings/{externalListingId}/analyse', [ListingIntelligenceAdminApiController::class, 'analyse'])->name('listing-intelligence.analyse');
                $router->post($base . '/listing-intelligence/runs/{run}/rewrites', [ListingIntelligenceAdminApiController::class, 'generateRewrite'])->name('listing-intelligence.rewrites.store');
                $router->post($base . '/marketplaces/{connection}/listing-rewrites/{rewrite}/prepare', [ListingIntelligenceAdminApiController::class, 'prepareWrite'])->name('listing-intelligence.rewrites.prepare');

                $router->get($base . '/credentials', [CommerceCredentialAdminApiController::class, 'index'])->name('credentials.index');
                $router->get($base . '/credentials/{provider}', [CommerceCredentialAdminApiController::class, 'show'])->name('credentials.show');
                $router->put($base . '/credentials/{provider}', [CommerceCredentialAdminApiController::class, 'store'])->name('credentials.store');
                $router->post($base . '/credentials/{provider}/rotate', [CommerceCredentialAdminApiController::class, 'rotate'])->name('credentials.rotate');
                $router->delete($base . '/credentials/{provider}', [CommerceCredentialAdminApiController::class, 'revoke'])->name('credentials.revoke');
                $router->post($base . '/credentials/{provider}/test', [CommerceCredentialAdminApiController::class, 'test'])->name('credentials.test');

                $router->post($base . '/orders/{order}/fulfillments', [FulfillmentApiController::class, 'store'])->name('fulfillments.store');
                $router->get($base . '/orders/{order}/fulfillments', [FulfillmentApiController::class, 'index'])->name('fulfillments.index');
                $router->post($base . '/fulfillments/{fulfillment}/processing', [FulfillmentApiController::class, 'processing'])->name('fulfillments.processing');
                $router->post($base . '/fulfillments/{fulfillment}/shipped', [FulfillmentApiController::class, 'shipped'])->name('fulfillments.shipped');
                $router->post($base . '/fulfillments/{fulfillment}/delivered', [FulfillmentApiController::class, 'delivered'])->name('fulfillments.delivered');
                $router->post($base . '/fulfillments/{fulfillment}/cancel', [FulfillmentApiController::class, 'cancel'])->name('fulfillments.cancel');

                $router->get($base . '/rentals/accounts', [RentalHireAdminApiController::class, 'accounts'])->name('rentals.accounts.index');
                $router->post($base . '/rentals/accounts', [RentalHireAdminApiController::class, 'storeAccount'])->name('rentals.accounts.store');
                $router->get($base . '/rentals/accounts/{account}', [RentalHireAdminApiController::class, 'showAccount'])->name('rentals.accounts.show');
                $router->post($base . '/rentals/accounts/{account}/tokens/rotate', [RentalHireAdminApiController::class, 'rotateToken'])->name('rentals.accounts.tokens.rotate');
                $router->get($base . '/rentals/accounts/{account}/agreements', [RentalHireAdminApiController::class, 'agreements'])->name('rentals.agreements.index');
                $router->post($base . '/rentals/accounts/{account}/agreements', [RentalHireAdminApiController::class, 'storeAgreement'])->name('rentals.agreements.store');
                $router->put($base . '/rentals/agreements/{agreement}', [RentalHireAdminApiController::class, 'updateAgreement'])->name('rentals.agreements.update');
                $router->post($base . '/rentals/agreements/{agreement}/rates', [RentalHireAdminApiController::class, 'addRate'])->name('rentals.agreements.rates.store');
                $router->post($base . '/rentals/agreements/{agreement}/generate-charges', [RentalHireAdminApiController::class, 'generateCharges'])->name('rentals.agreements.charges.generate');
                $router->get($base . '/rentals/accounts/{account}/summary', [RentalHireAdminApiController::class, 'summary'])->name('rentals.accounts.summary');
                $router->get($base . '/rentals/accounts/{account}/ledger', [RentalHireAdminApiController::class, 'ledger'])->name('rentals.accounts.ledger');
                $router->post($base . '/rentals/accounts/{account}/payment-requests', [RentalHireAdminApiController::class, 'paymentRequest'])->name('rentals.payment-requests');
                $router->post($base . '/rentals/accounts/{account}/payments', [RentalHireAdminApiController::class, 'recordPayment'])->name('rentals.payments.store');
                $router->post($base . '/rentals/payments/{payment}/allocate', [RentalHireAdminApiController::class, 'allocatePayment'])->name('rentals.payments.allocate');
                $router->post($base . '/rentals/payments/{payment}/confirm', [RentalHireAdminApiController::class, 'confirmPayment'])->name('rentals.payments.confirm');
                $router->post($base . '/rentals/payments/{payment}/reverse', [RentalHireAdminApiController::class, 'reversePayment'])->name('rentals.payments.reverse');
                $router->post($base . '/rentals/charges/{charge}/adjustments', [RentalHireAdminApiController::class, 'adjustment'])->name('rentals.charges.adjust');
                $router->get($base . '/rentals/receipts/{receipt}', [RentalHireAdminApiController::class, 'receipt'])->name('rentals.receipts.show');
            });

        $router->middleware(['api'])->prefix('api/v3/chatbot/ecommerce/payment-webhooks')->name('api.v3.chatbot.ecommerce.payment-webhooks.')
            ->post('{provider}', [PaymentWebhookController::class, 'handle'])->name('handle');

        return $this;
    }

    public static function uninstall(): void
    {
        // Commerce orders, payment records, marketplace proposals, and rental ledgers are retained for audit and reconciliation.
    }
}
