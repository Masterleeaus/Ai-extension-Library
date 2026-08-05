<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private CatalogRepositoryContract $repository;
    private int $companyId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyId = 1;
        $this->repository = $this->app->make(CatalogRepositoryContract::class);
    }

    /** @test */
    public function can_create_a_category(): void
    {
        $data = [
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic products',
            'is_active' => true,
        ];

        $category = $this->repository->createCategory($data, $this->companyId);

        $this->assertNotNull($category['public_id']);
        $this->assertEquals('Electronics', $category['name']);
        $this->assertEquals('electronics', $category['slug']);
    }

    /** @test */
    public function can_create_nested_categories(): void
    {
        $parent = $this->repository->createCategory([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ], $this->companyId);

        $child = $this->repository->createCategory([
            'name' => 'Laptops',
            'slug' => 'laptops',
            'parent_public_id' => $parent['public_id'],
        ], $this->companyId);

        $this->assertNotNull($child['public_id']);
        $this->assertNotNull($child['parent_id']);
    }

    /** @test */
    public function can_list_categories(): void
    {
        $this->repository->createCategory([
            'name' => 'Category 1',
            'slug' => 'category-1',
        ], $this->companyId);

        $this->repository->createCategory([
            'name' => 'Category 2',
            'slug' => 'category-2',
        ], $this->companyId);

        $categories = $this->repository->listCategories($this->companyId);

        $this->assertGreaterThanOrEqual(2, count($categories));
    }

    /** @test */
    public function can_get_category_hierarchy(): void
    {
        $parent = $this->repository->createCategory([
            'name' => 'Parent Category',
            'slug' => 'parent',
        ], $this->companyId);

        $child = $this->repository->createCategory([
            'name' => 'Child Category',
            'slug' => 'child',
            'parent_public_id' => $parent['public_id'],
        ], $this->companyId);

        $hierarchy = $this->repository->getCategoryHierarchy($this->companyId, $parent['public_id']);

        $this->assertEquals('Parent Category', $hierarchy['name']);
        $this->assertIsArray($hierarchy['children']);
    }
}
