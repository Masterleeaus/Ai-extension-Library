<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\WorkCoreBusinessNetworkContract;
use PDO;
use Foundation\Support\JsonHelper;
use Foundation\Support\DateTimeHelper;

class WorkCoreBusinessNetwork implements WorkCoreBusinessNetworkContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'workcore_business_';
    private const TABLE_CRM_INTEGRATIONS = self::TABLE_PREFIX . 'crm_integrations';
    private const TABLE_CUSTOMERS = self::TABLE_PREFIX . 'customers';
    private const TABLE_KNOWLEDGE = self::TABLE_PREFIX . 'knowledge';
    private const TABLE_PRODUCTS = self::TABLE_PREFIX . 'products';
    private string $tablePrefix = self::TABLE_PREFIX;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createCustomer(
        string $tenantId,
        array $customerData
    ): string {
        $customerId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_CUSTOMERS . " (id, tenant_id, data, created_at)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $customerId,
            $tenantId,
            json_encode($customerData),
            DateTimeHelper::now(),
        ]);

        return $customerId;
    }

    public function getCustomer(
        string $tenantId,
        string $customerId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_CUSTOMERS . " WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$customerId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['data'] = JsonHelper::decode($result['data']);
        }

        return $result ?: null;
    }

    public function updateCustomer(
        string $tenantId,
        string $customerId,
        array $updates
    ): bool {
        $customer = $this->getCustomer($tenantId, $customerId);

        if (!$customer) {
            return false;
        }

        $mergedData = array_merge($customer['data'], $updates);

        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_CUSTOMERS . " SET data = ?, updated_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([json_encode($mergedData), DateTimeHelper::now(), $customerId, $tenantId]);
    }

    public function createProduct(
        string $tenantId,
        array $productData
    ): string {
        $productId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_PRODUCTS . " (id, tenant_id, data, created_at)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $productId,
            $tenantId,
            json_encode($productData),
            DateTimeHelper::now(),
        ]);

        return $productId;
    }

    public function queryProducts(
        string $tenantId,
        array $filters
    ): array {
        $query = "SELECT * FROM " . self::TABLE_PRODUCTS . " WHERE tenant_id = ?";
        $params = [$tenantId];

        if (!empty($filters['category'])) {
            $query .= " AND JSON_EXTRACT(data, '$.category') = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = $filters['limit'];
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $result['data'] = JsonHelper::decode($result['data']);
        }

        return $results;
    }

    public function storeKnowledge(
        string $tenantId,
        string $knowledgeId,
        array $content
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_KNOWLEDGE . " (id, tenant_id, content, stored_at)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE content = ?, updated_at = ?"
        );

        $contentJson = json_encode($content);
        $now = DateTimeHelper::now();

        return $stmt->execute([
            $knowledgeId,
            $tenantId,
            $contentJson,
            $now,
            $contentJson,
            $now,
        ]);
    }

    public function searchKnowledge(
        string $tenantId,
        string $query
    ): array {
        $stmt = $this->db->prepare(
            "SELECT id, content FROM " . self::TABLE_KNOWLEDGE . " WHERE tenant_id = ? AND MATCH(content) AGAINST(? IN BOOLEAN MODE)"
        );

        $stmt->execute([$tenantId, $query]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $result['content'] = JsonHelper::decode($result['content']);
        }

        return $results;
    }

    public function integrateWithCRM(
        string $tenantId,
        array $crmConfig
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_CRM_INTEGRATIONS . " (tenant_id, config, integrated_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE config = ?, updated_at = ?"
        );

        $configJson = json_encode($crmConfig);
        $now = DateTimeHelper::now();

        return $stmt->execute([
            $tenantId,
            $configJson,
            $now,
            $configJson,
            $now,
        ]);
    }
}
