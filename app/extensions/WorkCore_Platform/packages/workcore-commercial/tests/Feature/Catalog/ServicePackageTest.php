<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ServicePackageTest extends TestCase
{
    use RefreshDatabase;

    private CatalogRepositoryContract $repository;
    private int $companyId;
    private int $actorId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyId = 1;
        $this->actorId = 1;
        $this->repository = $this->app->make(CatalogRepositoryContract::class);
    }

    /** @test */
    public function can_create_a_service_package(): void
    {
        $data = [
            'name' => 'Premium Hair Cut Package',
            'description' => 'Includes haircut and styling',
            'base_price' => 150.00,
            'currency' => 'AUD',
            'services' => ['haircut', 'styling'],
            'addons' => ['coloring', 'treatment'],
            'is_active' => true,
        ];

        $package = $this->repository->createServicePackage($data, $this->companyId, $this->actorId);

        $this->assertNotNull($package['public_id']);
        $this->assertEquals('Premium Hair Cut Package', $package['name']);
        $this->assertEquals(150.00, $package['base_price']);
    }

    /** @test */
    public function can_list_service_packages(): void
    {
        $this->repository->createServicePackage([
            'name' => 'Package 1',
            'base_price' => 100.00,
            'services' => ['service1'],
        ], $this->companyId, $this->actorId);

        $this->repository->createServicePackage([
            'name' => 'Package 2',
            'base_price' => 200.00,
            'services' => ['service2'],
        ], $this->companyId, $this->actorId);

        $packages = $this->repository->listServicePackages($this->companyId);

        $this->assertGreaterThanOrEqual(2, $packages->total());
    }

    /** @test */
    public function cannot_create_service_package_without_services(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->repository->createServicePackage([
            'name' => 'Invalid Package',
            'base_price' => 100.00,
            'services' => [],
        ], $this->companyId, $this->actorId);
    }
}
