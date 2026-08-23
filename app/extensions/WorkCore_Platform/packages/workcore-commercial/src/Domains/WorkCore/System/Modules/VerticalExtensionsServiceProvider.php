<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules;

use App\Domains\WorkCore\System\Modules\VerticalExtensions\Automotive\AutomotiveExtensionServiceProvider;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\Booking\BookingExtensionServiceProvider;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\ECommerce\ECommerceExtensionServiceProvider;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\FieldServicesExtensionServiceProvider;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\Fitness\FitnessExtensionServiceProvider;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\Hospitality\HospitalityExtensionServiceProvider;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\HireRental\HireRentalExtensionServiceProvider;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\RealEstate\RealEstateExtensionServiceProvider;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\Salons\SalonsExtensionServiceProvider;
use Illuminate\Support\ServiceProvider;

class VerticalExtensionsServiceProvider extends ServiceProvider
{
    /**
     * Register all vertical extension service providers
     */
    public function register(): void
    {
        // Register all 9 vertical extensions
        $this->app->register(FieldServicesExtensionServiceProvider::class);
        $this->app->register(HospitalityExtensionServiceProvider::class);
        $this->app->register(RealEstateExtensionServiceProvider::class);
        $this->app->register(SalonsExtensionServiceProvider::class);
        $this->app->register(FitnessExtensionServiceProvider::class);
        $this->app->register(AutomotiveExtensionServiceProvider::class);
        $this->app->register(ECommerceExtensionServiceProvider::class);
        $this->app->register(HireRentalExtensionServiceProvider::class);
        $this->app->register(BookingExtensionServiceProvider::class);
    }

    /**
     * Bootstrap application services
     */
    public function boot(): void
    {
        $this->registerVerticalRegistry();
    }

    /**
     * Register the vertical extension registry
     */
    private function registerVerticalRegistry(): void
    {
        $registry = [
            'field-services' => [
                'name' => 'Field & Home Services',
                'sub_verticals' => 24,
            ],
            'hospitality' => [
                'name' => 'Hospitality & Accommodation Services',
                'sub_verticals' => 20,
            ],
            'real-estate' => [
                'name' => 'Real Estate & Property Management',
                'sub_verticals' => 20,
            ],
            'salons' => [
                'name' => 'Salons & Personal Care',
                'sub_verticals' => 20,
            ],
            'fitness' => [
                'name' => 'Fitness & Membership',
                'sub_verticals' => 21,
            ],
            'automotive' => [
                'name' => 'Automotive Services',
                'sub_verticals' => 22,
            ],
            'ecommerce' => [
                'name' => 'E-Commerce & Retail',
                'sub_verticals' => 23,
            ],
            'hire-rental' => [
                'name' => 'Hire & Rental',
                'sub_verticals' => 22,
            ],
            'booking' => [
                'name' => 'Booking, Reservation & Capacity',
                'sub_verticals' => 26,
            ],
        ];

        $this->app->singleton('vertical.extensions.registry', function () use ($registry) {
            return $registry;
        });
    }
}
