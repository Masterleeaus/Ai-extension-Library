<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Catalog;

use App\Domains\WorkCore\System\Modules\Catalog\Domain\Product;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    /** @test */
    public function can_create_a_product(): void
    {
        $product = new Product(
            id: 'prod-123',
            companyId: 'comp-123',
            name: 'Test Product',
            sku: 'TEST-SKU',
            basePrice: 99.99,
        );

        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals('TEST-SKU', $product->sku);
        $this->assertEquals(99.99, $product->basePrice);
        $this->assertFalse($product->isPublished());
        $this->assertTrue($product->isActive());
    }

    /** @test */
    public function cannot_create_product_with_empty_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Product(
            id: '',
            companyId: 'comp-123',
            name: 'Test Product',
            sku: 'TEST-SKU',
            basePrice: 99.99,
        );
    }

    /** @test */
    public function cannot_create_product_with_negative_price(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Product(
            id: 'prod-123',
            companyId: 'comp-123',
            name: 'Test Product',
            sku: 'TEST-SKU',
            basePrice: -99.99,
        );
    }

    /** @test */
    public function can_publish_and_unpublish_product(): void
    {
        $product = new Product(
            id: 'prod-123',
            companyId: 'comp-123',
            name: 'Test Product',
            sku: 'TEST-SKU',
            basePrice: 99.99,
        );

        $product->publish();
        $this->assertTrue($product->isPublished());

        $product->unpublish();
        $this->assertFalse($product->isPublished());
    }

    /** @test */
    public function can_activate_and_deactivate_product(): void
    {
        $product = new Product(
            id: 'prod-123',
            companyId: 'comp-123',
            name: 'Test Product',
            sku: 'TEST-SKU',
            basePrice: 99.99,
        );

        $product->deactivate();
        $this->assertFalse($product->isActive());

        $product->activate();
        $this->assertTrue($product->isActive());
    }

    /** @test */
    public function can_calculate_effective_price(): void
    {
        $product = new Product(
            id: 'prod-123',
            companyId: 'comp-123',
            name: 'Test Product',
            sku: 'TEST-SKU',
            basePrice: 100.00,
            variants: [
                ['id' => 'var-1', 'price_modifier' => 10.00],
                ['id' => 'var-2', 'price_modifier' => 20.00],
            ],
        );

        $this->assertEquals(100.00, $product->getEffectivePrice());
        $this->assertEquals(110.00, $product->getEffectivePrice('var-1'));
        $this->assertEquals(120.00, $product->getEffectivePrice('var-2'));
    }
}
