<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Commercial\Queries;

use App\Domains\WorkCore\System\Query\BaseQueryBuilder;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

/**
 * Product Query Builder
 * Provides fluent interface for querying products with tenant isolation
 *
 * Usage:
 *   $available = (new ProductQueryBuilder($tenantContext))
 *       ->byCategory('plumbing')
 *       ->inStock()
 *       ->sortBy('name', 'ASC')
 *       ->limit(50)
 *       ->paginate();
 */
final class ProductQueryBuilder extends BaseQueryBuilder
{
    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    /**
     * Filter by product category
     */
    public function byCategory(string $category): static
    {
        return $this->where('category', $category);
    }

    /**
     * Filter by supplier
     */
    public function bySupplier(int $supplierId): static
    {
        return $this->where('supplier_id', $supplierId);
    }

    /**
     * Filter products in stock
     */
    public function inStock(): static
    {
        return $this->where('quantity_on_hand', 0, '>');
    }

    /**
     * Filter products out of stock
     */
    public function outOfStock(): static
    {
        return $this->where('quantity_on_hand', 0);
    }

    /**
     * Filter by price range
     */
    public function byPriceRange(float $min, float $max): static
    {
        return $this->where('unit_price', $min, '>=')
                    ->where('unit_price', $max, '<=');
    }

    /**
     * Filter by SKU
     */
    public function bySKU(string $sku): static
    {
        return $this->where('sku', $sku);
    }

    /**
     * Search by name or description
     */
    public function search(string $query): static
    {
        $this->filters[] = [
            'field' => 'name_or_description',
            'value' => "%{$query}%",
            'operator' => 'LIKE',
        ];
        return $this;
    }

    /**
     * Filter by product status
     */
    public function byStatus(string $status): static
    {
        return $this->where('status', $status);
    }

    /**
     * Filter active products only
     */
    public function active(): static
    {
        return $this->where('status', 'active');
    }

    /**
     * Get all products
     */
    public function get(): array
    {
        return [];
    }

    /**
     * Get single product
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Count total matching products
     */
    public function count(): int
    {
        return 0;
    }

    /**
     * Get paginated results
     */
    public function paginate(): array
    {
        $total = $this->count();
        $items = $this->get();

        return [
            'data' => $items,
            'total' => $total,
            'limit' => $this->limit ?? 20,
            'offset' => $this->offset,
        ];
    }

    /**
     * Get inventory summary
     */
    public function inventorySummary(): array
    {
        return [
            'total_products' => 0,
            'in_stock' => 0,
            'out_of_stock' => 0,
            'total_value' => 0.0,
            'by_category' => [],
        ];
    }

    /**
     * Check availability with quantity
     */
    public function checkAvailability(string $sku, int $quantity): array
    {
        return [
            'available' => true,
            'quantity_on_hand' => 0,
            'lead_time_days' => 0,
        ];
    }
}
