<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface CommerceContractContract
{
    public function getInventory(
        string $tenantId,
        string $productId
    ): ?array;

    public function updateInventory(
        string $tenantId,
        string $productId,
        int $quantity
    ): bool;

    public function getPricing(
        string $tenantId,
        string $productId,
        array $context = []
    ): ?array;

    public function processPayment(
        string $tenantId,
        array $paymentDetails
    ): array;

    public function createOrder(
        string $tenantId,
        array $order
    ): string;

    public function getOrder(string $tenantId, string $orderId): ?array;

    public function trackShipment(
        string $tenantId,
        string $orderId
    ): ?array;

    public function processRefund(
        string $tenantId,
        string $orderId,
        array $refundDetails
    ): bool;
}
