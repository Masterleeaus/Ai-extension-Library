<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface WorkCoreBusinessNetworkContract
{
    public function createCustomer(
        string $tenantId,
        array $customerData
    ): string;

    public function getCustomer(
        string $tenantId,
        string $customerId
    ): ?array;

    public function updateCustomer(
        string $tenantId,
        string $customerId,
        array $updates
    ): bool;

    public function createProduct(
        string $tenantId,
        array $productData
    ): string;

    public function queryProducts(
        string $tenantId,
        array $filters
    ): array;

    public function storeKnowledge(
        string $tenantId,
        string $knowledgeId,
        array $content
    ): bool;

    public function searchKnowledge(
        string $tenantId,
        string $query
    ): array;

    public function integrateWithCRM(
        string $tenantId,
        array $crmConfig
    ): bool;
}
