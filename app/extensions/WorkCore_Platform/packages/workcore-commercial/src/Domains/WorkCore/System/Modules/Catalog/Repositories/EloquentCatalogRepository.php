<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Repositories;

use App\Domains\WorkCore\System\Contracts\TenantContextContract;
use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;
use App\Domains\WorkCore\System\Persistence\TenantScopedRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class EloquentCatalogRepository extends TenantScopedRepository implements CatalogRepositoryContract
{
    public function __construct(ConnectionInterface $db, TenantContextContract $tenant)
    {
        parent::__construct($db, $tenant);
    }

    public function searchProducts(int $companyId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $this->assertCompany($companyId);

        return $this->db->table('workcore_catalog_products')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->when($filters['is_active'] ?? null, fn ($q, $v) => $q->where('is_active', (bool) $v))
            ->when($filters['is_published'] ?? null, fn ($q, $v) => $q->where('is_published', (bool) $v))
            ->when($filters['category_public_id'] ?? null, fn ($q, $v) => $this->filterByCategory($q, $v, $companyId))
            ->when($filters['product_type'] ?? null, fn ($q, $v) => $q->where('product_type', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($n) => $n->where('name', 'like', "%{$v}%")
                ->orWhere('description', 'like', "%{$v}%")
                ->orWhere('sku', 'like', "%{$v}%")))
            ->orderBy('name')
            ->paginate(max(1, min($perPage, 100)));
    }

    public function getProduct(int $companyId, string $publicId): ?array
    {
        $this->assertCompany($companyId);

        $product = $this->byPublicId('workcore_catalog_products', $companyId, $publicId);
        if (!$product) {
            return null;
        }

        return $this->enrichProduct($product);
    }

    public function createProduct(array $data, int $companyId, int $actorId): array
    {
        $this->assertActor($companyId, $actorId);

        $categoryId = null;
        if (!empty($data['category_public_id'])) {
            $category = $this->record('workcore_catalog_categories', (string) $data['category_public_id'], $companyId);
            $categoryId = (int) $category->id;
        }

        $publicId = (string) Str::ulid();
        $this->db->table('workcore_catalog_products')->insert([
            'public_id' => $publicId,
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sku' => $data['sku'],
            'base_price' => (float) $data['base_price'],
            'cost_price' => isset($data['cost_price']) ? (float) $data['cost_price'] : null,
            'currency' => $data['currency'] ?? 'AUD',
            'is_active' => $data['is_active'] ?? true,
            'is_published' => false,
            'stock_quantity' => (int) ($data['stock_quantity'] ?? 0),
            'product_type' => $data['product_type'] ?? 'standard',
            'attributes' => isset($data['attributes']) ? json_encode($data['attributes']) : null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'created_by_user_id' => $actorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->byPublicId('workcore_catalog_products', $companyId, $publicId);
    }

    public function updateProduct(string $publicId, array $data, int $companyId, int $actorId): array
    {
        $this->assertActor($companyId, $actorId);

        $product = $this->record('workcore_catalog_products', $publicId, $companyId);
        $categoryId = $product->category_id;

        if (!empty($data['category_public_id'])) {
            $category = $this->record('workcore_catalog_categories', (string) $data['category_public_id'], $companyId);
            $categoryId = (int) $category->id;
        }

        $this->db->table('workcore_catalog_products')
            ->where('id', $product->id)
            ->update([
                'name' => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'sku' => $data['sku'] ?? $product->sku,
                'base_price' => isset($data['base_price']) ? (float) $data['base_price'] : $product->base_price,
                'cost_price' => isset($data['cost_price']) ? (float) $data['cost_price'] : $product->cost_price,
                'category_id' => $categoryId,
                'is_active' => $data['is_active'] ?? $product->is_active,
                'product_type' => $data['product_type'] ?? $product->product_type,
                'attributes' => isset($data['attributes']) ? json_encode($data['attributes']) : $product->attributes,
                'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : $product->metadata,
                'updated_by_user_id' => $actorId,
                'updated_at' => now(),
            ]);

        return $this->byPublicId('workcore_catalog_products', $companyId, $publicId);
    }

    public function deleteProduct(string $publicId, int $companyId): void
    {
        $this->assertCompany($companyId);

        $product = $this->record('workcore_catalog_products', $publicId, $companyId);
        $this->db->table('workcore_catalog_products')
            ->where('id', $product->id)
            ->update(['deleted_at' => now()]);
    }

    public function getProductVariants(string $productPublicId, int $companyId): array
    {
        $this->assertCompany($companyId);

        $product = $this->record('workcore_catalog_products', $productPublicId, $companyId);

        return $this->db->table('workcore_catalog_product_variants')
            ->where('product_id', $product->id)
            ->where('company_id', $companyId)
            ->orderBy('variant_name')
            ->get()
            ->map(fn ($row) => $this->hydrateRecord($row))
            ->toArray();
    }

    public function createVariant(array $data, int $companyId): array
    {
        $this->assertCompany($companyId);

        $product = $this->record('workcore_catalog_products', (string) $data['product_public_id'], $companyId);

        $publicId = (string) Str::ulid();
        $this->db->table('workcore_catalog_product_variants')->insert([
            'public_id' => $publicId,
            'company_id' => $companyId,
            'product_id' => $product->id,
            'sku' => $data['sku'],
            'variant_name' => $data['variant_name'],
            'variant_attributes' => isset($data['variant_attributes']) ? json_encode($data['variant_attributes']) : null,
            'price_modifier' => (float) ($data['price_modifier'] ?? 0),
            'cost_modifier' => (float) ($data['cost_modifier'] ?? 0),
            'stock_quantity' => (int) ($data['stock_quantity'] ?? 0),
            'is_active' => $data['is_active'] ?? true,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->byPublicId('workcore_catalog_product_variants', $companyId, $publicId);
    }

    public function updateVariant(string $variantPublicId, array $data, int $companyId): array
    {
        $this->assertCompany($companyId);

        $variant = $this->record('workcore_catalog_product_variants', $variantPublicId, $companyId);

        $this->db->table('workcore_catalog_product_variants')
            ->where('id', $variant->id)
            ->update([
                'variant_name' => $data['variant_name'] ?? $variant->variant_name,
                'sku' => $data['sku'] ?? $variant->sku,
                'price_modifier' => isset($data['price_modifier']) ? (float) $data['price_modifier'] : $variant->price_modifier,
                'cost_modifier' => isset($data['cost_modifier']) ? (float) $data['cost_modifier'] : $variant->cost_modifier,
                'stock_quantity' => isset($data['stock_quantity']) ? (int) $data['stock_quantity'] : $variant->stock_quantity,
                'is_active' => $data['is_active'] ?? $variant->is_active,
                'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : $variant->metadata,
                'updated_at' => now(),
            ]);

        return $this->byPublicId('workcore_catalog_product_variants', $companyId, $variantPublicId);
    }

    public function listCategories(int $companyId, ?string $parentId = null): array
    {
        $this->assertCompany($companyId);

        $query = $this->db->table('workcore_catalog_categories')
            ->where('company_id', $companyId)
            ->where('is_active', true);

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $parent = $this->record('workcore_catalog_categories', $parentId, $companyId);
            $query->where('parent_id', $parent->id);
        }

        return $query->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => $this->hydrateRecord($row))
            ->toArray();
    }

    public function createCategory(array $data, int $companyId): array
    {
        $this->assertCompany($companyId);

        $parentId = null;
        if (!empty($data['parent_public_id'])) {
            $parent = $this->record('workcore_catalog_categories', (string) $data['parent_public_id'], $companyId);
            $parentId = (int) $parent->id;
        }

        $publicId = (string) Str::ulid();
        $this->db->table('workcore_catalog_categories')->insert([
            'public_id' => $publicId,
            'company_id' => $companyId,
            'parent_id' => $parentId,
            'name' => $data['name'],
            'slug' => Str::slug($data['slug'] ?? $data['name']),
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $data['is_active'] ?? true,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->byPublicId('workcore_catalog_categories', $companyId, $publicId);
    }

    public function getCategoryHierarchy(int $companyId, string $categoryPublicId): array
    {
        $this->assertCompany($companyId);

        $category = $this->record('workcore_catalog_categories', $categoryPublicId, $companyId);

        return $this->buildCategoryHierarchy($category, $companyId);
    }

    public function createServicePackage(array $data, int $companyId, int $actorId): array
    {
        $this->assertActor($companyId, $actorId);

        $publicId = (string) Str::ulid();
        $this->db->table('workcore_catalog_service_packages')->insert([
            'public_id' => $publicId,
            'company_id' => $companyId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'base_price' => (float) $data['base_price'],
            'currency' => $data['currency'] ?? 'AUD',
            'is_active' => $data['is_active'] ?? true,
            'services' => isset($data['services']) ? json_encode($data['services']) : json_encode([]),
            'addons' => isset($data['addons']) ? json_encode($data['addons']) : null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'created_by_user_id' => $actorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->byPublicId('workcore_catalog_service_packages', $companyId, $publicId);
    }

    public function getServicePackage(string $packagePublicId, int $companyId): ?array
    {
        $this->assertCompany($companyId);

        return $this->byPublicId('workcore_catalog_service_packages', $companyId, $packagePublicId);
    }

    public function listServicePackages(int $companyId, int $perPage = 25): LengthAwarePaginator
    {
        $this->assertCompany($companyId);

        return $this->db->table('workcore_catalog_service_packages')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(max(1, min($perPage, 100)));
    }

    public function uploadMedia(array $data, int $companyId): array
    {
        $this->assertCompany($companyId);

        $productId = null;
        if (!empty($data['product_public_id'])) {
            $product = $this->record('workcore_catalog_products', (string) $data['product_public_id'], $companyId);
            $productId = (int) $product->id;
        }

        $publicId = (string) Str::ulid();
        $this->db->table('workcore_catalog_media')->insert([
            'public_id' => $publicId,
            'company_id' => $companyId,
            'product_id' => $productId,
            'media_type' => $data['media_type'],
            'url' => $data['url'],
            'alt_text' => $data['alt_text'] ?? null,
            'file_name' => $data['file_name'] ?? null,
            'mime_type' => $data['mime_type'] ?? null,
            'file_size' => isset($data['file_size']) ? (int) $data['file_size'] : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_primary' => $data['is_primary'] ?? false,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->byPublicId('workcore_catalog_media', $companyId, $publicId);
    }

    public function getProductMedia(string $productPublicId, int $companyId): array
    {
        $this->assertCompany($companyId);

        $product = $this->record('workcore_catalog_products', $productPublicId, $companyId);

        return $this->db->table('workcore_catalog_media')
            ->where('product_id', $product->id)
            ->where('company_id', $companyId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($row) => $this->hydrateRecord($row))
            ->toArray();
    }

    public function publishToChannel(string $productPublicId, string $channelName, array $data, int $companyId): array
    {
        $this->assertCompany($companyId);

        $product = $this->record('workcore_catalog_products', $productPublicId, $companyId);

        $record = $this->db->table('workcore_catalog_channel_publishing')
            ->where('product_id', $product->id)
            ->where('channel_name', $channelName)
            ->first();

        $publicId = (string) Str::ulid();
        if ($record) {
            $this->db->table('workcore_catalog_channel_publishing')
                ->where('id', $record->id)
                ->update([
                    'is_published' => $data['is_published'] ?? true,
                    'channel_price' => isset($data['channel_price']) ? (float) $data['channel_price'] : $record->channel_price,
                    'channel_sku' => $data['channel_sku'] ?? $record->channel_sku,
                    'is_visible' => $data['is_visible'] ?? true,
                    'published_at' => ($data['is_published'] ?? true) ? now() : $record->published_at,
                    'scheduled_for' => $data['scheduled_for'] ?? null,
                    'channel_metadata' => isset($data['channel_metadata']) ? json_encode($data['channel_metadata']) : $record->channel_metadata,
                    'updated_at' => now(),
                ]);

            return $this->hydrateRecord($this->db->table('workcore_catalog_channel_publishing')
                ->where('id', $record->id)
                ->first());
        }

        $this->db->table('workcore_catalog_channel_publishing')->insert([
            'public_id' => $publicId,
            'company_id' => $companyId,
            'product_id' => $product->id,
            'channel_name' => $channelName,
            'is_published' => $data['is_published'] ?? true,
            'channel_price' => isset($data['channel_price']) ? (float) $data['channel_price'] : null,
            'channel_sku' => $data['channel_sku'] ?? null,
            'is_visible' => $data['is_visible'] ?? true,
            'published_at' => ($data['is_published'] ?? true) ? now() : null,
            'scheduled_for' => $data['scheduled_for'] ?? null,
            'channel_metadata' => isset($data['channel_metadata']) ? json_encode($data['channel_metadata']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->byPublicId('workcore_catalog_channel_publishing', $companyId, $publicId);
    }

    public function getChannelPublishing(string $productPublicId, string $channelName, int $companyId): ?array
    {
        $this->assertCompany($companyId);

        $product = $this->record('workcore_catalog_products', $productPublicId, $companyId);

        $record = $this->db->table('workcore_catalog_channel_publishing')
            ->where('product_id', $product->id)
            ->where('channel_name', $channelName)
            ->first();

        return $record ? $this->hydrateRecord($record) : null;
    }

    public function syncInventory(string $productPublicId, int $newQuantity, int $companyId): void
    {
        $this->assertCompany($companyId);

        $product = $this->record('workcore_catalog_products', $productPublicId, $companyId);

        $this->db->table('workcore_catalog_products')
            ->where('id', $product->id)
            ->update([
                'stock_quantity' => $newQuantity,
                'updated_at' => now(),
            ]);
    }

    // Helper methods

    private function filterByCategory($query, string $categoryPublicId, int $companyId)
    {
        $category = $this->record('workcore_catalog_categories', $categoryPublicId, $companyId);

        return $query->where('category_id', $category->id);
    }

    private function enrichProduct($product): array
    {
        $product = $this->hydrateRecord($product);
        $variants = $this->db->table('workcore_catalog_product_variants')
            ->where('product_id', $product['id'])
            ->get()
            ->map(fn ($row) => $this->hydrateRecord($row))
            ->toArray();

        $media = $this->db->table('workcore_catalog_media')
            ->where('product_id', $product['id'])
            ->get()
            ->map(fn ($row) => $this->hydrateRecord($row))
            ->toArray();

        $product['variants'] = $variants;
        $product['media'] = $media;

        return $product;
    }

    private function buildCategoryHierarchy($category, int $companyId): array
    {
        $children = $this->db->table('workcore_catalog_categories')
            ->where('parent_id', $category->id)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->map(fn ($child) => $this->buildCategoryHierarchy($child, $companyId))
            ->toArray();

        return [
            'public_id' => $category->public_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'children' => $children,
        ];
    }

    private function hydrateRecord($row): array
    {
        $data = (array) $row;
        if (isset($data['attributes']) && is_string($data['attributes'])) {
            $data['attributes'] = json_decode($data['attributes'], true) ?? [];
        }
        if (isset($data['metadata']) && is_string($data['metadata'])) {
            $data['metadata'] = json_decode($data['metadata'], true) ?? [];
        }
        if (isset($data['variant_attributes']) && is_string($data['variant_attributes'])) {
            $data['variant_attributes'] = json_decode($data['variant_attributes'], true) ?? [];
        }
        if (isset($data['services']) && is_string($data['services'])) {
            $data['services'] = json_decode($data['services'], true) ?? [];
        }
        if (isset($data['addons']) && is_string($data['addons'])) {
            $data['addons'] = json_decode($data['addons'], true) ?? [];
        }
        if (isset($data['channel_metadata']) && is_string($data['channel_metadata'])) {
            $data['channel_metadata'] = json_decode($data['channel_metadata'], true) ?? [];
        }

        return $data;
    }
}
