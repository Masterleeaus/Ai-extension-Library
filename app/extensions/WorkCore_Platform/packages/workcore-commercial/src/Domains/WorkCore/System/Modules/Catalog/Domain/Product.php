<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Domain;

use DomainException;
use InvalidArgumentException;

final class Product
{
    private bool $isPublished = false;
    private bool $isActive = true;

    /**
     * @param array<string, mixed> $variants
     * @param array<string, mixed> $media
     * @param array<string, mixed> $channelPublishing
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly string $sku,
        public readonly float $basePrice,
        public readonly ?float $costPrice = null,
        public readonly string $currency = 'AUD',
        public readonly ?string $description = null,
        public readonly ?string $categoryId = null,
        public readonly string $productType = 'standard',
        public readonly array $variants = [],
        public readonly array $media = [],
        public readonly array $channelPublishing = [],
        public readonly array $attributes = [],
    ) {
        if (trim($id) === '' || trim($companyId) === '' || trim($name) === '' || trim($sku) === '') {
            throw new InvalidArgumentException('Product id, company, name and SKU are required.');
        }
        if ($basePrice < 0) {
            throw new InvalidArgumentException('Product base price cannot be negative.');
        }
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function publish(): void
    {
        if ($this->isPublished) {
            throw new DomainException('Product is already published.');
        }
        $this->isPublished = true;
    }

    public function unpublish(): void
    {
        if (!$this->isPublished) {
            throw new DomainException('Product is not published.');
        }
        $this->isPublished = false;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function getEffectivePrice(?string $variantId = null): float
    {
        if ($variantId === null) {
            return $this->basePrice;
        }

        foreach ($this->variants as $variant) {
            if ($variant['id'] === $variantId) {
                return $this->basePrice + (float)($variant['price_modifier'] ?? 0);
            }
        }

        return $this->basePrice;
    }

    public function getTotalInventory(): int
    {
        $total = 0;
        foreach ($this->variants as $variant) {
            $total += (int)($variant['stock_quantity'] ?? 0);
        }
        return $total;
    }

    public function isAvailable(): bool
    {
        return $this->isActive && $this->getTotalInventory() > 0;
    }
}
