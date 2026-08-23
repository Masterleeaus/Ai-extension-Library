<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ProductTest extends TestCase
{
    use RefreshDatabase;

    private CatalogRepositoryContract $repository;
    private int $companyId;
    private int $actorId;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock setup - in a real test these would come from seeders
        $this->companyId = 1;
        $this->actorId = 1;

        $this->repository = $this->app->make(CatalogRepositoryContract::class);
    }

    /** @test */
    public function can_create_a_product(): void
    {
        $data = [
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-' . Str::random(4),
            'description' => 'A test product',
            'base_price' => 99.99,
            'cost_price' => 50.00,
            'currency' => 'AUD',
            'product_type' => 'standard',
            'is_active' => true,
            'stock_quantity' => 100,
        ];

        $product = $this->repository->createProduct($data, $this->companyId, $this->actorId);

        $this->assertNotNull($product['public_id']);
        $this->assertEquals('Test Product', $product['name']);
        $this->assertEquals('AUD', $product['currency']);
    }

    /** @test */
    public function can_update_a_product(): void
    {
        $data = [
            'name' => 'Original Product',
            'sku' => 'ORIG-SKU-' . Str::random(4),
            'base_price' => 99.99,
        ];

        $product = $this->repository->createProduct($data, $this->companyId, $this->actorId);

        $updated = $this->repository->updateProduct(
            $product['public_id'],
            ['name' => 'Updated Product', 'base_price' => 149.99],
            $this->companyId,
            $this->actorId
        );

        $this->assertEquals('Updated Product', $updated['name']);
        $this->assertEquals(149.99, $updated['base_price']);
    }

    /** @test */
    public function can_create_product_variants(): void
    {
        $product = $this->repository->createProduct([
            'name' => 'Variable Product',
            'sku' => 'VAR-SKU-' . Str::random(4),
            'base_price' => 99.99,
            'product_type' => 'variable',
        ], $this->companyId, $this->actorId);

        $variant = $this->repository->createVariant([
            'product_public_id' => $product['public_id'],
            'variant_name' => 'Red - Size M',
            'sku' => 'VAR-RED-M-' . Str::random(4),
            'variant_attributes' => ['color' => 'red', 'size' => 'M'],
            'price_modifier' => 10.00,
            'stock_quantity' => 50,
        ], $this->companyId);

        $this->assertNotNull($variant['public_id']);
        $this->assertEquals('Red - Size M', $variant['variant_name']);
        $this->assertEquals(10.00, $variant['price_modifier']);
    }

    /** @test */
    public function can_search_products(): void
    {
        $this->repository->createProduct([
            'name' => 'Searchable Product',
            'sku' => 'SEARCH-' . Str::random(4),
            'base_price' => 99.99,
            'is_active' => true,
        ], $this->companyId, $this->actorId);

        $results = $this->repository->searchProducts($this->companyId, ['q' => 'Searchable'], 25);

        $this->assertGreaterThan(0, $results->total());
    }

    /** @test */
    public function cannot_create_product_without_required_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->repository->createProduct([
            'name' => '',
            'sku' => '',
            'base_price' => 99.99,
        ], $this->companyId, $this->actorId);
    }

    /** @test */
    public function can_delete_a_product(): void
    {
        $product = $this->repository->createProduct([
            'name' => 'Deletable Product',
            'sku' => 'DEL-' . Str::random(4),
            'base_price' => 99.99,
        ], $this->companyId, $this->actorId);

        $this->repository->deleteProduct($product['public_id'], $this->companyId);

        $deleted = $this->repository->getProduct($this->companyId, $product['public_id']);
        $this->assertNull($deleted);
    }
}
