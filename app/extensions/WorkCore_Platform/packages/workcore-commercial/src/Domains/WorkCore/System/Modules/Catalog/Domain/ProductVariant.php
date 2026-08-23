<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Domain;

use InvalidArgumentException;

final class ProductVariant
{
    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly string $productId,
        public readonly string $sku,
        public readonly string $variantName,
        public readonly float $priceModifier = 0,
        public readonly float $costModifier = 0,
        public readonly int $stockQuantity = 0,
        public readonly int $reservedQuantity = 0,
        public readonly bool $isActive = true,
        public readonly array $attributes = [],
        public readonly array $metadata = [],
    ) {
        if (trim($id) === '' || trim($productId) === '' || trim($sku) === '' || trim($variantName) === '') {
            throw new InvalidArgumentException('Variant id, product id, SKU and name are required.');
        }
    }

    public function getAvailableQuantity(): int
    {
        return max(0, $this->stockQuantity - $this->reservedQuantity);
    }

    public function calculatePrice(float $basePrice): float
    {
        return $basePrice + $this->priceModifier;
    }

    public function calculateCost(float $baseCost): float
    {
        return $baseCost + $this->costModifier;
    }

    public function canFulfillQuantity(int $quantity): bool
    {
        return $this->isActive && $this->getAvailableQuantity() >= $quantity;
    }
}
