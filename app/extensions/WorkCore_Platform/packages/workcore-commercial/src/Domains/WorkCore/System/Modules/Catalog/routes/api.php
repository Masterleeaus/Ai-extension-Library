<?php

declare(strict_types=1);

use App\Domains\WorkCore\System\Modules\Catalog\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix((string) config('workcore.catalog.route_prefix'))->middleware((array) config('workcore.catalog.middleware'))->name('api.workcore.v1.')->group(function (): void {
    // Product endpoints
    Route::get('products', [CatalogController::class, 'searchProducts'])->name('products.index');
    Route::post('products', [CatalogController::class, 'createProduct'])->name('products.store');
    Route::get('products/{product}', [CatalogController::class, 'getProduct'])->name('products.show');
    Route::put('products/{product}', [CatalogController::class, 'updateProduct'])->name('products.update');
    Route::delete('products/{product}', [CatalogController::class, 'deleteProduct'])->name('products.destroy');

    // Product Variant endpoints
    Route::post('variants', [CatalogController::class, 'createVariant'])->name('variants.store');

    // Category endpoints
    Route::get('categories', [CatalogController::class, 'listCategories'])->name('categories.index');
    Route::post('categories', [CatalogController::class, 'createCategory'])->name('categories.store');
    Route::get('categories/{category}/hierarchy', [CatalogController::class, 'getCategoryHierarchy'])->name('categories.hierarchy');

    // Service Package endpoints
    Route::get('service-packages', [CatalogController::class, 'listServicePackages'])->name('service-packages.index');
    Route::post('service-packages', [CatalogController::class, 'createServicePackage'])->name('service-packages.store');

    // Media endpoints
    Route::post('media', [CatalogController::class, 'uploadMedia'])->name('media.store');
    Route::get('products/{product}/media', [CatalogController::class, 'getProductMedia'])->name('products.media');

    // Channel Publishing endpoints
    Route::post('products/publish', [CatalogController::class, 'publishToChannel'])->name('products.publish');
});
