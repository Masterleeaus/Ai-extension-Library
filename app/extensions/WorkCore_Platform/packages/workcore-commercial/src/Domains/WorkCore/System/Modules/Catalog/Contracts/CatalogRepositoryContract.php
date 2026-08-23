<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CatalogRepositoryContract
{
    /**
     * List all products with filtering and pagination
     */
    public function searchProducts(int $companyId, array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /**
     * Get a single product with all its data
     */
    public function getProduct(int $companyId, string $publicId): ?array;

    /**
     * Create a new product
     */
    public function createProduct(array $data, int $companyId, int $actorId): array;

    /**
     * Update an existing product
     */
    public function updateProduct(string $publicId, array $data, int $companyId, int $actorId): array;

    /**
     * Delete (soft delete) a product
     */
    public function deleteProduct(string $publicId, int $companyId): void;

    /**
     * Get all product variants
     */
    public function getProductVariants(string $productPublicId, int $companyId): array;

    /**
     * Create product variant
     */
    public function createVariant(array $data, int $companyId): array;

    /**
     * Update product variant
     */
    public function updateVariant(string $variantPublicId, array $data, int $companyId): array;

    /**
     * List all categories
     */
    public function listCategories(int $companyId, ?string $parentId = null): array;

    /**
     * Create a category
     */
    public function createCategory(array $data, int $companyId): array;

    /**
     * Get category hierarchy
     */
    public function getCategoryHierarchy(int $companyId, string $categoryPublicId): array;

    /**
     * Create service package
     */
    public function createServicePackage(array $data, int $companyId, int $actorId): array;

    /**
     * Get service package
     */
    public function getServicePackage(string $packagePublicId, int $companyId): ?array;

    /**
     * List service packages
     */
    public function listServicePackages(int $companyId, int $perPage = 25): LengthAwarePaginator;

    /**
     * Upload media for a product
     */
    public function uploadMedia(array $data, int $companyId): array;

    /**
     * Get product media
     */
    public function getProductMedia(string $productPublicId, int $companyId): array;

    /**
     * Publish product to channel
     */
    public function publishToChannel(string $productPublicId, string $channelName, array $data, int $companyId): array;

    /**
     * Get channel publishing info
     */
    public function getChannelPublishing(string $productPublicId, string $channelName, int $companyId): ?array;

    /**
     * Sync inventory with Supply domain
     */
    public function syncInventory(string $productPublicId, int $newQuantity, int $companyId): void;
}
