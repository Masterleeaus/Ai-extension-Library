<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface EcommerceIntegrationContract
{
    public function registerEcommerceCatalog(
        string $tenantId,
        array $catalogConfig
    ): string;

    public function getCatalogStatus(
        string $tenantId,
        string $catalogId
    ): ?array;

    public function syncProducts(
        string $tenantId,
        string $catalogId,
        array $products
    ): int;

    public function processEcommerceOrder(
        string $tenantId,
        array $orderData
    ): string;

    public function getOrderStatus(
        string $tenantId,
        string $orderId
    ): ?array;

    public function handlePaymentCallback(
        string $tenantId,
        array $paymentData
    ): bool;

    public function generateEcommerceMigrationPlan(
        string $tenantId
    ): array;

    public function validateEcommerceIntegration(
        string $tenantId
    ): array;
}
