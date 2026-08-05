<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Providers;

use App\Domains\WorkCore\System\Actions\{ActionDefinition, BusinessActionRegistry};
use App\Domains\WorkCore\System\Modules\Catalog\Actions\{
    CreateCategory,
    CreateProduct,
    CreateProductVariant,
    CreateServicePackage,
    DeleteProduct,
    GetCategoryHierarchy,
    GetProduct,
    GetProductMedia,
    ListCategories,
    ListServicePackages,
    PublishToChannel,
    SearchProducts,
    UpdateProduct,
    UploadMedia,
};
use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;
use App\Domains\WorkCore\System\Modules\Catalog\Repositories\EloquentCatalogRepository;
use App\Domains\WorkCore\System\ReadModels\{ReadModelDefinition, ReadModelRegistry};
use Illuminate\Support\ServiceProvider;

final class WorkCatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CatalogRepositoryContract::class, EloquentCatalogRepository::class);

        $actions = $this->app->make(BusinessActionRegistry::class);
        $definitions = [
            ['workcore.catalog.product.create', CreateProduct::class, 'medium', 'create_product'],
            ['workcore.catalog.product.update', UpdateProduct::class, 'medium', 'update_product'],
            ['workcore.catalog.product.delete', DeleteProduct::class, 'high', 'delete_product'],
            ['workcore.catalog.product.get', GetProduct::class, 'low', 'view'],
            ['workcore.catalog.variant.create', CreateProductVariant::class, 'medium', 'create_variant'],
            ['workcore.catalog.category.create', CreateCategory::class, 'low', 'create_category'],
            ['workcore.catalog.category.list', ListCategories::class, 'low', 'view'],
            ['workcore.catalog.category.hierarchy', GetCategoryHierarchy::class, 'low', 'view'],
            ['workcore.catalog.service_package.create', CreateServicePackage::class, 'medium', 'create_package'],
            ['workcore.catalog.service_package.list', ListServicePackages::class, 'low', 'view'],
            ['workcore.catalog.media.upload', UploadMedia::class, 'medium', 'upload_media'],
            ['workcore.catalog.product.media', GetProductMedia::class, 'low', 'view'],
            ['workcore.catalog.product.publish_to_channel', PublishToChannel::class, 'medium', 'publish_product'],
        ];

        foreach ($definitions as [$key, $handler, $risk, $permission]) {
            $actions->register(
                new ActionDefinition(
                    $key,
                    $handler,
                    $risk,
                    true,
                    'workcore.catalog',
                    (string) config("workcore.catalog.permissions.{$permission}"),
                    ['domain' => 'catalog', 'canonical_owner' => 'WorkCore Catalog'],
                ),
            );
        }

        $reads = $this->app->make(ReadModelRegistry::class);
        $view = (string) config('workcore.catalog.permissions.view');
        $reads->register(new ReadModelDefinition('workcore.catalog.products.search', SearchProducts::class, 'workcore.catalog', permission: $view));
    }

    public function boot(): void
    {
        if ((bool) config('workcore.catalog.routes_enabled', false)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }
    }
}
