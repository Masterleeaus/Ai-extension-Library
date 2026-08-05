<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Catalog;

use App\Domains\WorkCore\System\Modules\Catalog\Domain\ProductVariant;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProductVariantTest extends TestCase
{
    /** @test */
    public function can_create_a_variant(): void
    {
        $variant = new ProductVariant(
            id: 'var-123',
            productId: 'prod-123',
            sku: 'VAR-SKU',
            variantName: 'Red - Size M',
            priceModifier: 10.00,
            stockQuantity: 100,
        );

        $this->assertEquals('Red - Size M', $variant->variantName);
        $this->assertEquals(10.00, $variant->priceModifier);
        $this->assertEquals(100, $variant->stockQuantity);
    }

    /** @test */
    public function cannot_create_variant_without_required_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ProductVariant(
            id: '',
            productId: 'prod-123',
            sku: 'VAR-SKU',
            variantName: 'Test',
        );
    }

    /** @test */
    public function can_calculate_available_quantity(): void
    {
        $variant = new ProductVariant(
            id: 'var-123',
            productId: 'prod-123',
            sku: 'VAR-SKU',
            variantName: 'Test',
            stockQuantity: 100,
            reservedQuantity: 30,
        );

        $this->assertEquals(70, $variant->getAvailableQuantity());
    }

    /** @test */
    public function can_calculate_price_with_modifier(): void
    {
        $variant = new ProductVariant(
            id: 'var-123',
            productId: 'prod-123',
            sku: 'VAR-SKU',
            variantName: 'Premium',
            priceModifier: 20.00,
        );

        $this->assertEquals(120.00, $variant->calculatePrice(100.00));
    }

    /** @test */
    public function can_check_fulfillment_capability(): void
    {
        $variant = new ProductVariant(
            id: 'var-123',
            productId: 'prod-123',
            sku: 'VAR-SKU',
            variantName: 'Test',
            stockQuantity: 100,
            reservedQuantity: 50,
            isActive: true,
        );

        $this->assertTrue($variant->canFulfillQuantity(50));
        $this->assertFalse($variant->canFulfillQuantity(51));
    }
}
