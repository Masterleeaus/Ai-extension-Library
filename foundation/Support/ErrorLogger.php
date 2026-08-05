<?php

declare(strict_types=1);

namespace Foundation\Support;

use Foundation\Exceptions\DatabaseException;
use PDO;
use DateTime;

/**
 * Structured error logging for database operations.
 *
 * Logs database errors to the error_logs table with full context including:
 * - Error message and SQL state
 * - Operation type and parameters (sanitized)
 * - Stack trace and retry information
 * - Tenant context and user information
 */
class ErrorLogger
{
    private PDO $db;
    private string $errorTable = 'error_logs';
    private ?string $tenantId = null;
    private ?string $userId = null;

    public function __construct(PDO $db, ?string $tenantId = null, ?string $userId = null)
    {
        $this->db = $db;
        $this->tenantId = $tenantId;
        $this->userId = $userId;
    }

    /**
     * Log a database exception with full context.
     */
    public function logException(DatabaseException $exception, string $operation = 'UNKNOWN'): void
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->errorTable} 
                 (id, tenant_id, user_id, operation, error_message, sql_state, is_transient, 
                  context, stack_trace, logged_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            if ($stmt === false) {
                error_log("Failed to prepare error log statement: " . json_encode($this->db->errorInfo()));
                return;
            }

            $id = bin2hex(random_bytes(16));
            $context = $exception->getContext() ?? [];
            $stackTrace = json_encode($this->sanitizeStackTrace($exception->getTraceAsString()));

            $result = $stmt->execute([
                $id,
                $this->tenantId,
                $this->userId,
                $operation,
                $exception->getMessage(),
                $exception->getSqlState(),
                $exception->isTransient() ? 1 : 0,
                json_encode($context),
                $stackTrace,
                (new DateTime())->format('c'),
            ]);

            if (!$result) {
                error_log("Failed to execute error log insert: " . json_encode($stmt->errorInfo()));
            }
        } catch (\Exception $e) {
            error_log("Failed to log database exception: {$e->getMessage()}");
        }
    }

    /**
     * Get recent errors for a tenant.
     */
    public function getRecentErrors(int $limit = 100, int $offset = 0): array
    {
        try {
            $query = "SELECT * FROM {$this->errorTable} WHERE 1=1";
            $params = [];

            if ($this->tenantId) {
                $query .= " AND tenant_id = ?";
                $params[] = $this->tenantId;
            }

            $query .= " ORDER BY logged_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($query);
            if ($stmt === false) {
                return [];
            }

            if (!$stmt->execute($params)) {
                return [];
            }

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as &$result) {
                $result['context'] = json_decode($result['context'], true);
                $result['is_transient'] = (bool) $result['is_transient'];
            }

            return $results;
        } catch (\Exception $e) {
            error_log("Failed to retrieve recent errors: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Count errors for a tenant.
     */
    public function countErrors(?string $operation = null): int
    {
        try {
            $query = "SELECT COUNT(*) as count FROM {$this->errorTable} WHERE 1=1";
            $params = [];

            if ($this->tenantId) {
                $query .= " AND tenant_id = ?";
                $params[] = $this->tenantId;
            }

            if ($operation) {
                $query .= " AND operation = ?";
                $params[] = $operation;
            }

            $stmt = $this->db->prepare($query);
            if ($stmt === false) {
                return 0;
            }

            if (!$stmt->execute($params)) {
                return 0;
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            error_log("Failed to count errors: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Create a new logger instance.
     */
    public static function make(
        PDO $db,
        ?string $tenantId = null,
        ?string $userId = null
    ): self {
        return new self($db, $tenantId, $userId);
    }

    /**
     * Sanitize stack trace to remove sensitive information.
     */
    private function sanitizeStackTrace(string $trace): string
    {
        $trace = preg_replace(
            '#/[a-z0-9_/]+\.php#i',
            '/***REDACTED***.php',
            $trace
        );

        return $trace;
    }
}
