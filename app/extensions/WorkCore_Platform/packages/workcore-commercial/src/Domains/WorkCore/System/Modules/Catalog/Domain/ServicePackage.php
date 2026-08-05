<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Domain;

use InvalidArgumentException;

final class ServicePackage
{
    /**
     * @param array<string, mixed> $services
     * @param array<string, mixed> $addons
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly float $basePrice,
        public readonly string $currency = 'AUD',
        public readonly ?string $description = null,
        public readonly bool $isActive = true,
        public readonly array $services = [],
        public readonly array $addons = [],
        public readonly array $metadata = [],
    ) {
        if (trim($id) === '' || trim($companyId) === '' || trim($name) === '') {
            throw new InvalidArgumentException('Service package id, company and name are required.');
        }
        if ($basePrice < 0) {
            throw new InvalidArgumentException('Service package base price cannot be negative.');
        }
        if (empty($services)) {
            throw new InvalidArgumentException('Service package requires at least one service.');
        }
    }

    public function calculatePrice(?float $addonModifier = null): float
    {
        $total = $this->basePrice;
        if ($addonModifier !== null && $addonModifier > 0) {
            $total += $addonModifier;
        }
        return $total;
    }

    public function applyDiscount(float $discountPercent): float
    {
        if ($discountPercent < 0 || $discountPercent > 100) {
            throw new InvalidArgumentException('Discount percent must be between 0 and 100.');
        }
        return $this->basePrice * (1 - $discountPercent / 100);
    }

    public function getServiceCount(): int
    {
        return count($this->services);
    }

    public function getAddonCount(): int
    {
        return count($this->addons);
    }
}
