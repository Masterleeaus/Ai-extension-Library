<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\CommerceContractContract;
use PDO;

class CommerceContract implements CommerceContractContract
{
    private PDO $db;
    private string $tablePrefix = 'commerce_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getInventory(
        string $tenantId,
        string $productId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}inventory WHERE tenant_id = ? AND product_id = ?"
        );

        $stmt->execute([$tenantId, $productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateInventory(
        string $tenantId,
        string $productId,
        int $quantity
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}inventory (tenant_id, product_id, quantity, updated_at)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = ?, updated_at = ?"
        );

        $now = date('c');
        return $stmt->execute([
            $tenantId,
            $productId,
            $quantity,
            $now,
            $quantity,
            $now,
        ]);
    }

    public function getPricing(
        string $tenantId,
        string $productId,
        array $context = []
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}pricing WHERE tenant_id = ? AND product_id = ?"
        );

        $stmt->execute([$tenantId, $productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['tiers'] = json_decode($result['tiers'], true);
        }

        return $result ?: null;
    }

    public function processPayment(
        string $tenantId,
        array $paymentDetails
    ): array {
        $paymentId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}payments (id, tenant_id, details, status, processed_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $paymentId,
            $tenantId,
            json_encode($paymentDetails),
            'processing',
            date('c'),
        ]);

        return [
            'payment_id' => $paymentId,
            'status' => 'processing',
        ];
    }

    public function createOrder(
        string $tenantId,
        array $order
    ): string {
        $orderId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}orders (id, tenant_id, data, status, created_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $orderId,
            $tenantId,
            json_encode($order),
            'pending',
            date('c'),
        ]);

        return $orderId;
    }

    public function getOrder(
        string $tenantId,
        string $orderId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}orders WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$orderId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['data'] = json_decode($result['data'], true);
        }

        return $result ?: null;
    }

    public function trackShipment(
        string $tenantId,
        string $orderId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}shipments WHERE tenant_id = ? AND order_id = ?"
        );

        $stmt->execute([$tenantId, $orderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function processRefund(
        string $tenantId,
        string $orderId,
        array $refundDetails
    ): bool {
        $refundId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}refunds (id, tenant_id, order_id, details, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $refundId,
            $tenantId,
            $orderId,
            json_encode($refundDetails),
            'pending',
            date('c'),
        ]);
    }
}
