<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Http\Controllers;

use App\Domains\WorkCore\System\Actions\{ActionRequest, ActionResult, BusinessActionDispatcher};
use App\Domains\WorkCore\System\Api\ApiResponseFactory;
use App\Domains\WorkCore\System\Modules\Catalog\Actions\SearchProducts;
use App\Domains\WorkCore\System\Modules\Catalog\Http\Requests\{
    PublishToChannelRequest,
    StoreCategoryRequest,
    StoreProductRequest,
    StoreServicePackageRequest,
    StoreVariantRequest,
    UpdateProductRequest,
    UploadMediaRequest,
};
use Illuminate\Http\{JsonResponse, Request};

final class CatalogController
{
    public function searchProducts(Request $request, SearchProducts $search, ApiResponseFactory $api): JsonResponse
    {
        return $api->paginated(
            $search->execute($request->only(['q', 'category_public_id', 'is_active', 'is_published', 'product_type']), $request->integer('per_page', 25)),
            static fn (array $row): array => $row,
        );
    }

    public function getProduct(string $product, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        $tenant = workcore_tenant();
        $result = $dispatcher->dispatch(
            new ActionRequest('workcore.catalog.product.get', ['product_public_id' => $product], $tenant->companyId(), (int) $tenant->userId(), '', '', 'api'),
        );

        return $api->action(new ActionResult($result->actionKey, $result->data, $result->correlationId, $result->auditId, $result->eventIds, $result->replayed));
    }

    public function createProduct(StoreProductRequest $request, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        return $this->dispatch('workcore.catalog.product.create', $request->validated(), $request, $dispatcher, $api);
    }

    public function updateProduct(string $product, UpdateProductRequest $request, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        return $this->dispatch('workcore.catalog.product.update', ['product_public_id' => $product] + $request->validated(), $request, $dispatcher, $api);
    }

    public function deleteProduct(string $product, Request $request, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        return $this->dispatch('workcore.catalog.product.delete', ['product_public_id' => $product], $request, $dispatcher, $api);
    }

    public function createVariant(StoreVariantRequest $request, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        return $this->dispatch('workcore.catalog.variant.create', $request->validated(), $request, $dispatcher, $api);
    }

    public function listCategories(Request $request, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        $tenant = workcore_tenant();
        $result = $dispatcher->dispatch(
            new ActionRequest('workcore.catalog.category.list', $request->only(['parent_public_id']), $tenant->companyId(), (int) $tenant->userId(), '', '', 'api'),
        );

        return $api->action(new ActionResult($result->actionKey, $result->data, $result->correlationId, $result->auditId, $result->eventIds, $result->replayed));
    }

    public function createCategory(StoreCategoryRequest $request, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        return $this->dispatch('workcore.catalog.category.create', $request->validated(), $request, $dispatcher, $api);
    }

    public function getCategoryHierarchy(string $category, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        $tenant = workcore_tenant();
        $result = $dispatcher->dispatch(
            new ActionRequest('workcore.catalog.category.hierarchy', ['category_public_id' => $category], $tenant->companyId(), (int) $tenant->userId(), '', '', 'api'),
        );

        return $api->action(new ActionResult($result->actionKey, $result->data, $result->correlationId, $result->auditId, $result->eventIds, $result->replayed));
    }

    public function createServicePackage(StoreServicePackageRequest $request, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        return $this->dispatch('workcore.catalog.service_package.create', $request->validated(), $request, $dispatcher, $api);
    }

    public function listServicePackages(Request $request, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        $tenant = workcore_tenant();
        $result = $dispatcher->dispatch(
            new ActionRequest('workcore.catalog.service_package.list', ['per_page' => $request->integer('per_page', 25)], $tenant->companyId(), (int) $tenant->userId(), '', '', 'api'),
        );

        return $api->action(new ActionResult($result->actionKey, $result->data, $result->correlationId, $result->auditId, $result->eventIds, $result->replayed));
    }

    public function uploadMedia(UploadMediaRequest $request, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        return $this->dispatch('workcore.catalog.media.upload', $request->validated(), $request, $dispatcher, $api);
    }

    public function getProductMedia(string $product, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        $tenant = workcore_tenant();
        $result = $dispatcher->dispatch(
            new ActionRequest('workcore.catalog.product.media', ['product_public_id' => $product], $tenant->companyId(), (int) $tenant->userId(), '', '', 'api'),
        );

        return $api->action(new ActionResult($result->actionKey, $result->data, $result->correlationId, $result->auditId, $result->eventIds, $result->replayed));
    }

    public function publishToChannel(PublishToChannelRequest $request, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        return $this->dispatch('workcore.catalog.product.publish_to_channel', $request->validated(), $request, $dispatcher, $api);
    }

    private function dispatch(string $action, array $payload, Request $request, BusinessActionDispatcher $dispatcher, ApiResponseFactory $api): JsonResponse
    {
        $tenant = workcore_tenant();
        $idempotency = trim((string) $request->header('Idempotency-Key'));
        abort_if($idempotency === '', 422, 'Idempotency-Key header is required.');

        $result = $dispatcher->dispatch(
            new ActionRequest($action, $payload, $tenant->companyId(), (int) $tenant->userId(), $idempotency, $request->header('X-WorkCore-Confirmation'), 'api'),
        );

        return $api->action(new ActionResult($result->actionKey, $result->data, $result->correlationId, $result->auditId, $result->eventIds, $result->replayed));
    }
}
