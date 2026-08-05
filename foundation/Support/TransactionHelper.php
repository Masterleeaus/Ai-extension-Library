<?php

declare(strict_types=1);

namespace Foundation\Support;

use Foundation\Exceptions\DatabaseException;
use PDO;
use PDOException;
use Throwable;

/**
 * Helper for managing database transactions with automatic rollback and logging.
 *
 * Provides wrapper for begin/commit/rollback with:
 * - Automatic rollback on exception
 * - Logging for transaction events
 * - Atomic operation guarantees (all or nothing)
 * - Nested transaction detection
 * - Comprehensive error handling
 */
class TransactionHelper
{
    private PDO $db;
    private ?callable $logger = null;
    private bool $inTransaction = false;
    private int $transactionDepth = 0;

    public function __construct(PDO $db, ?callable $logger = null)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Execute a callback within a database transaction.
     *
     * Automatically begins transaction, executes callback, commits on success,
     * and rolls back on any exception.
     *
     * @param callable $callback The operation to execute atomically
     * @param string $operationName Name of operation for logging
     * @param array $context Additional context for logging
     * @return mixed The return value from the callback
     *
     * @throws DatabaseException if transaction fails
     * @throws Throwable if callback throws (will rollback first)
     */
    public function executeInTransaction(
        callable $callback,
        string $operationName = 'database_operation',
        array $context = []
    ) {
        $startTime = microtime(true);
        $transactionId = bin2hex(random_bytes(8));

        try {
            $this->log(
                'transaction.begin',
                "Beginning transaction for {$operationName}",
                array_merge(['transaction_id' => $transactionId], $context)
            );

            $this->beginTransaction();

            try {
                $result = $callback($this->db);

                $this->commit();

                $duration = microtime(true) - $startTime;
                $this->log(
                    'transaction.commit',
                    "Transaction committed for {$operationName}",
                    array_merge([
                        'transaction_id' => $transactionId,
                        'duration_ms' => round($duration * 1000, 2),
                    ], $context)
                );

                return $result;
            } catch (Throwable $e) {
                $this->rollback();

                $duration = microtime(true) - $startTime;
                $this->log(
                    'transaction.rollback',
                    "Transaction rolled back for {$operationName}: {$e->getMessage()}",
                    array_merge([
                        'transaction_id' => $transactionId,
                        'duration_ms' => round($duration * 1000, 2),
                        'error' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                    ], $context),
                    'warning'
                );

                throw $e;
            }
        } catch (PDOException $e) {
            $this->log(
                'transaction.error',
                "PDO error during transaction: {$e->getMessage()}",
                array_merge([
                    'transaction_id' => $transactionId,
                    'error' => $e->getMessage(),
                ], $context),
                'error'
            );

            throw new DatabaseException(
                "Transaction failed: {$e->getMessage()}",
                0,
                $e,
                null,
                false,
                array_merge(['operation' => $operationName], $context)
            );
        }
    }

    /**
     * Begin a database transaction.
     *
     * @throws DatabaseException if transaction cannot be started
     */
    public function beginTransaction(): void
    {
        if ($this->inTransaction) {
            $this->transactionDepth++;
            return;
        }

        try {
            if (!$this->db->beginTransaction()) {
                throw new DatabaseException(
                    'Failed to begin transaction',
                    0,
                    null,
                    null,
                    false,
                    ['operation' => 'beginTransaction']
                );
            }

            $this->inTransaction = true;
            $this->transactionDepth = 1;

            $this->log(
                'transaction.started',
                'Database transaction started'
            );
        } catch (PDOException $e) {
            $this->log(
                'transaction.error',
                "Failed to begin transaction: {$e->getMessage()}",
                [],
                'error'
            );

            throw new DatabaseException(
                "Cannot begin transaction: {$e->getMessage()}",
                0,
                $e,
                null,
                false,
                ['operation' => 'beginTransaction']
            );
        }
    }

    /**
     * Commit the current transaction.
     *
     * @throws DatabaseException if commit fails
     */
    public function commit(): void
    {
        if (!$this->inTransaction) {
            $this->log(
                'transaction.warning',
                'Attempted to commit when no transaction is active',
                [],
                'warning'
            );
            return;
        }

        $this->transactionDepth--;

        if ($this->transactionDepth > 0) {
            return;
        }

        try {
            if (!$this->db->commit()) {
                throw new DatabaseException(
                    'Failed to commit transaction',
                    0,
                    null,
                    null,
                    false,
                    ['operation' => 'commit']
                );
            }

            $this->inTransaction = false;
            $this->transactionDepth = 0;

            $this->log(
                'transaction.committed',
                'Database transaction committed'
            );
        } catch (PDOException $e) {
            $this->inTransaction = false;
            $this->transactionDepth = 0;

            $this->log(
                'transaction.error',
                "Failed to commit transaction: {$e->getMessage()}",
                [],
                'error'
            );

            throw new DatabaseException(
                "Cannot commit transaction: {$e->getMessage()}",
                0,
                $e,
                null,
                false,
                ['operation' => 'commit']
            );
        }
    }

    /**
     * Rollback the current transaction.
     *
     * @throws DatabaseException if rollback fails
     */
    public function rollback(): void
    {
        if (!$this->inTransaction) {
            $this->log(
                'transaction.warning',
                'Attempted to rollback when no transaction is active',
                [],
                'warning'
            );
            return;
        }

        try {
            if (!$this->db->rollBack()) {
                throw new DatabaseException(
                    'Failed to rollback transaction',
                    0,
                    null,
                    null,
                    false,
                    ['operation' => 'rollBack']
                );
            }

            $this->inTransaction = false;
            $this->transactionDepth = 0;

            $this->log(
                'transaction.rolled_back',
                'Database transaction rolled back'
            );
        } catch (PDOException $e) {
            $this->inTransaction = false;
            $this->transactionDepth = 0;

            $this->log(
                'transaction.error',
                "Failed to rollback transaction: {$e->getMessage()}",
                [],
                'error'
            );

            throw new DatabaseException(
                "Cannot rollback transaction: {$e->getMessage()}",
                0,
                $e,
                null,
                false,
                ['operation' => 'rollBack']
            );
        }
    }

    /**
     * Check if currently in an active transaction.
     */
    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    /**
     * Get the current transaction depth (for nested transactions).
     */
    public function getTransactionDepth(): int
    {
        return $this->transactionDepth;
    }

    /**
     * Set a logger callable.
     *
     * Logger should accept: (level, message, context)
     */
    public function setLogger(?callable $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Log a message if logger is set.
     *
     * @param string $event The event type/key
     * @param string $message The log message
     * @param array $context Additional context data
     * @param string $level The log level (info, warning, error)
     */
    private function log(
        string $event,
        string $message,
        array $context = [],
        string $level = 'info'
    ): void {
        if (!$this->logger) {
            return;
        }

        $logContext = array_merge(
            ['event' => $event, 'level' => $level],
            $context
        );

        call_user_func($this->logger, $level, $message, $logContext);
    }

    /**
     * Create a new instance.
     */
    public static function make(PDO $db, ?callable $logger = null): self
    {
        return new self($db, $logger);
    }
}
