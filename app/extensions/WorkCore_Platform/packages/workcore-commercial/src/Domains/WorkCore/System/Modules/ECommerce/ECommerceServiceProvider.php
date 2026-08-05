<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce;

use Illuminate\Support\ServiceProvider;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\CartService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\CheckoutService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\OrderService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\FulfillmentService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\ReviewService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\TaxService;

class ECommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register singleton services
        $this->app->singleton(TaxService::class, function ($app) {
            return new TaxService();
        });

        $this->app->singleton(CartService::class, function ($app) {
            return new CartService();
        });

        $this->app->singleton(ReviewService::class, function ($app) {
            return new ReviewService();
        });

        $this->app->singleton(FulfillmentService::class, function ($app) {
            return new FulfillmentService();
        });

        $this->app->singleton(OrderService::class, function ($app) {
            return new OrderService($app->make(TaxService::class));
        });

        $this->app->singleton(CheckoutService::class, function ($app) {
            return new CheckoutService(
                $app->make(TaxService::class),
                $app->make(OrderService::class)
            );
        });
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
    }

    public function provides(): array
    {
        return [
            CartService::class,
            CheckoutService::class,
            OrderService::class,
            FulfillmentService::class,
            ReviewService::class,
            TaxService::class,
        ];
    }
}
