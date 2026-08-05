<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\WorkCoreBusinessNetworkContract;
use Foundation\Exceptions\DatabaseException;
use Foundation\Support\DatabaseHelper;
use Foundation\Support\ErrorLogger;
use Foundation\Support\JsonHelper;
use Foundation\Support\DateTimeHelper;
use PDO;

/**
 * WorkCore Business Network Implementation with comprehensive PDO error handling.
 *
 * All database operations are wrapped with try-catch blocks, automatic retry logic
 * for transient errors, and structured error logging.
 */
class WorkCoreBusinessNetwork implements WorkCoreBusinessNetworkContract
{
    private PDO $db;
    private DatabaseHelper $dbHelper;
    private ErrorLogger $errorLogger;
    private const TABLE_PREFIX = 'workcore_business_';
    private const TABLE_CRM_INTEGRATIONS = self::TABLE_PREFIX . 'crm_integrations';
    private const TABLE_CUSTOMERS = self::TABLE_PREFIX . 'customers';
    private const TABLE_KNOWLEDGE = self::TABLE_PREFIX . 'knowledge';
    private const TABLE_PRODUCTS = self::TABLE_PREFIX . 'products';
    private string $tablePrefix = self::TABLE_PREFIX;
    private ?string $tenantId = null;

    public function __construct(PDO $db, ?string $tenantId = null)
    {
        $this->db = $db;
        $this->tenantId = $tenantId;
        $this->dbHelper = DatabaseHelper::make($db);
        $this->errorLogger = ErrorLogger::make($db, $tenantId);
    }

    /**
     * Create a new customer with comprehensive error handling.
     *
     * @throws DatabaseException if the operation fails after retries
     */
    public function createCustomer(
        string $tenantId,
        array $customerData
    ): string {
        $customerId = bin2hex(random_bytes(16));

        try {
            $stmt = $this->dbHelper->safePrepare(
                "INSERT INTO " . self::TABLE_CUSTOMERS . " (id, tenant_id, data, created_at)
                 VALUES (?, ?, ?, ?)"
            );

            $this->dbHelper->safeExecute($stmt, [
                $customerId,
                $tenantId,
                json_encode($customerData),
                DateTimeHelper::now(),
            ], 'INSERT_CUSTOMER');

            return $customerId;
        } catch (DatabaseException $e) {
            $this->errorLogger->logException($e, 'CREATE_CUSTOMER');
            throw $e;
        }
    }

    /**
     * Get customer by ID with comprehensive error handling.
     *
     * @throws DatabaseException if the operation fails after retries
     */
    public function getCustomer(
        string $tenantId,
        string $customerId
    ): ?array {
        try {
            $stmt = $this->dbHelper->safePrepare(
                "SELECT * FROM " . self::TABLE_CUSTOMERS . " WHERE id = ? AND tenant_id = ?"
            );

            $this->dbHelper->safeExecute($stmt, [$customerId, $tenantId], 'SELECT_CUSTOMER');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $result['data'] = JsonHelper::decode($result['data']);
            }

            return $result ?: null;
        } catch (DatabaseException $e) {
            $this->errorLogger->logException($e, 'GET_CUSTOMER');
            throw $e;
        }
    }

    /**
     * Update customer with comprehensive error handling.
     *
     * @throws DatabaseException if the operation fails after retries
     */
    public function updateCustomer(
        string $tenantId,
        string $customerId,
        array $updates
    ): bool {
        try {
            $customer = $this->getCustomer($tenantId, $customerId);

            if (!$customer) {
                return false;
            }

            $mergedData = array_merge($customer['data'], $updates);

            $stmt = $this->dbHelper->safePrepare(
                "UPDATE " . self::TABLE_CUSTOMERS . " SET data = ?, updated_at = ? WHERE id = ? AND tenant_id = ?"
            );

            return $this->dbHelper->safeExecute(
                $stmt,
                [json_encode($mergedData), DateTimeHelper::now(), $customerId, $tenantId],
                'UPDATE_CUSTOMER'
            );
        } catch (DatabaseException $e) {
            $this->errorLogger->logException($e, 'UPDATE_CUSTOMER');
            throw $e;
        }
    }

    /**
     * Create a new product with comprehensive error handling.
     *
     * @throws DatabaseException if the operation fails after retries
     */
    public function createProduct(
        string $tenantId,
        array $productData
    ): string {
        $productId = bin2hex(random_bytes(16));

        try {
            $stmt = $this->dbHelper->safePrepare(
                "INSERT INTO " . self::TABLE_PRODUCTS . " (id, tenant_id, data, created_at)
                 VALUES (?, ?, ?, ?)"
            );

            $this->dbHelper->safeExecute($stmt, [
                $productId,
                $tenantId,
                json_encode($productData),
                DateTimeHelper::now(),
            ], 'INSERT_PRODUCT');

            return $productId;
        } catch (DatabaseException $e) {
            $this->errorLogger->logException($e, 'CREATE_PRODUCT');
            throw $e;
        }
    }

    /**
     * Query products with filters and comprehensive error handling.
     *
     * @throws DatabaseException if the operation fails after retries
     */
    public function queryProducts(
        string $tenantId,
        array $filters
    ): array {
        try {
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

            $stmt = $this->dbHelper->safePrepare($query);
            $this->dbHelper->safeExecute($stmt, $params, 'SELECT_PRODUCTS');

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as &$result) {
                $result['data'] = JsonHelper::decode($result['data']);
            }

            return $results;
        } catch (DatabaseException $e) {
            $this->errorLogger->logException($e, 'QUERY_PRODUCTS');
            throw $e;
        }
    }

    /**
     * Store knowledge with comprehensive error handling.
     *
     * @throws DatabaseException if the operation fails after retries
     */
    public function storeKnowledge(
        string $tenantId,
        string $knowledgeId,
        array $content
    ): bool {
        try {
            $stmt = $this->dbHelper->safePrepare(
                "INSERT INTO " . self::TABLE_KNOWLEDGE . " (id, tenant_id, content, stored_at)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE content = ?, updated_at = ?"
            );

            $contentJson = json_encode($content);
            $now = DateTimeHelper::now();

            return $this->dbHelper->safeExecute($stmt, [
                $knowledgeId,
                $tenantId,
                $contentJson,
                $now,
                $contentJson,
                $now,
            ], 'UPSERT_KNOWLEDGE');
        } catch (DatabaseException $e) {
            $this->errorLogger->logException($e, 'STORE_KNOWLEDGE');
            throw $e;
        }
    }

    /**
     * Search knowledge with comprehensive error handling.
     *
     * @throws DatabaseException if the operation fails after retries
     */
    public function searchKnowledge(
        string $tenantId,
        string $query
    ): array {
        try {
            $stmt = $this->dbHelper->safePrepare(
                "SELECT id, content FROM " . self::TABLE_KNOWLEDGE . " WHERE tenant_id = ? AND MATCH(content) AGAINST(? IN BOOLEAN MODE)"
            );

            $this->dbHelper->safeExecute($stmt, [$tenantId, $query], 'SEARCH_KNOWLEDGE');
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as &$result) {
                $result['content'] = JsonHelper::decode($result['content']);
            }

            return $results;
        } catch (DatabaseException $e) {
            $this->errorLogger->logException($e, 'SEARCH_KNOWLEDGE');
            throw $e;
        }
    }

    /**
     * Integrate with CRM with comprehensive error handling.
     *
     * @throws DatabaseException if the operation fails after retries
     */
    public function integrateWithCRM(
        string $tenantId,
        array $crmConfig
    ): bool {
        try {
            $stmt = $this->dbHelper->safePrepare(
                "INSERT INTO " . self::TABLE_CRM_INTEGRATIONS . " (tenant_id, config, integrated_at)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE config = ?, updated_at = ?"
            );

            $configJson = json_encode($crmConfig);
            $now = DateTimeHelper::now();

            return $this->dbHelper->safeExecute($stmt, [
                $tenantId,
                $configJson,
                $now,
                $configJson,
                $now,
            ], 'UPSERT_CRM_INTEGRATION');
        } catch (DatabaseException $e) {
            $this->errorLogger->logException($e, 'INTEGRATE_WITH_CRM');
            throw $e;
        }
    }
}
