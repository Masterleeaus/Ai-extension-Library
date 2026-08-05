<?php

declare(strict_types=1);

namespace Foundation\Support;

use Foundation\Exceptions\DatabaseException;
use PDO;
use PDOStatement;
use PDOException;

/**
 * Helper for safe PDO database operations with comprehensive error handling.
 *
 * Provides wrappers around PDO prepare/execute with:
 * - Automatic error checking
 * - Exception throwing with context
 * - Transient error detection
 * - Parameter sanitization for logging
 * - Retry logic support
 */
class DatabaseHelper
{
    /**
     * Maximum retry attempts for transient errors.
     */
    private const MAX_RETRIES = 3;

    /**
     * Delay between retries (milliseconds).
     */
    private const RETRY_DELAY_MS = 100;

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Safely prepare a statement.
     *
     * @throws DatabaseException if prepare fails
     */
    public function safePrepare(string $sql): PDOStatement
    {
        try {
            $stmt = $this->db->prepare($sql);

            if ($stmt === false) {
                $error = $this->db->errorInfo();
                throw new DatabaseException(
                    "Failed to prepare statement: {$error[2]}",
                    (int) $error[0],
                    null,
                    $error[0],
                    DatabaseException::isTransientSqlState($error[0]),
                    ['sql' => $this->sanitizeForLogging($sql)]
                );
            }

            return $stmt;
        } catch (PDOException $e) {
            $error = $this->db->errorInfo();
            throw new DatabaseException(
                "Database prepare error: {$e->getMessage()}",
                0,
                $e,
                $error[0] ?? null,
                DatabaseException::isTransientSqlState($error[0] ?? null),
                ['sql' => $this->sanitizeForLogging($sql)]
            );
        }
    }

    /**
     * Safely execute a statement with automatic retry logic.
     *
     * @param PDOStatement $stmt The prepared statement
     * @param array $params Parameters to bind
     * @param string $operationType Type of operation for logging (INSERT, SELECT, etc.)
     * @param int $retryCount Current retry attempt (internal use)
     *
     * @throws DatabaseException if execution fails after all retries
     */
    public function safeExecute(
        PDOStatement $stmt,
        array $params = [],
        string $operationType = 'EXECUTE',
        int $retryCount = 0
    ): bool {
        try {
            $result = $stmt->execute($params);

            if (!$result) {
                $error = $stmt->errorInfo();
                $isTransient = DatabaseException::isTransientSqlState($error[0]);

                // Retry on transient errors
                if ($isTransient && $retryCount < self::MAX_RETRIES) {
                    usleep(self::RETRY_DELAY_MS * 1000);
                    return $this->safeExecute($stmt, $params, $operationType, $retryCount + 1);
                }

                throw new DatabaseException(
                    "Failed to execute {$operationType}: {$error[2]}",
                    (int) $error[0],
                    null,
                    $error[0],
                    $isTransient,
                    [
                        'operation' => $operationType,
                        'params' => $this->sanitizeParams($params),
                        'retries' => $retryCount,
                    ]
                );
            }

            return true;
        } catch (PDOException $e) {
            $error = $stmt->errorInfo();
            $isTransient = DatabaseException::isTransientSqlState($error[0] ?? null);

            // Retry on transient errors
            if ($isTransient && $retryCount < self::MAX_RETRIES) {
                usleep(self::RETRY_DELAY_MS * 1000);
                return $this->safeExecute($stmt, $params, $operationType, $retryCount + 1);
            }

            throw new DatabaseException(
                "Database execute error: {$e->getMessage()}",
                0,
                $e,
                $error[0] ?? null,
                $isTransient,
                [
                    'operation' => $operationType,
                    'params' => $this->sanitizeParams($params),
                    'retries' => $retryCount,
                ]
            );
        }
    }

    /**
     * Helper to sanitize parameter values for logging.
     *
     * Removes sensitive values like passwords, tokens, keys.
     */
    private function sanitizeParams(array $params): array
    {
        $sensitiveKeys = [
            'password', 'passwd', 'pwd', 'secret', 'token', 'apikey',
            'api_key', 'bearer', 'auth', 'credential', 'key', 'private',
        ];

        $sanitized = [];
        foreach ($params as $key => $value) {
            $keyStr = (string) $key;

            // Check if key contains sensitive keywords
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos($keyStr, $sensitiveKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_string($value) && strlen($value) > 100) {
                $sanitized[$key] = substr($value, 0, 100) . '...';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Helper to sanitize SQL for logging.
     *
     * Removes or masks literal values to avoid logging sensitive data.
     */
    private function sanitizeForLogging(string $sql): string
    {
        // Replace string literals with placeholders
        $sql = preg_replace("/('([^']*)')/", "'***'", $sql);

        // Limit SQL length for logs
        if (strlen($sql) > 500) {
            $sql = substr($sql, 0, 500) . '...';
        }

        return $sql;
    }

    /**
     * Create a new helper instance.
     */
    public static function make(PDO $db): self
    {
        return new self($db);
    }
}
