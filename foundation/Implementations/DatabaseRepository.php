<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\DatabaseRepositoryContract;
use PDO;

/**
 * DatabaseRepository provides a unified abstraction layer for database operations.
 *
 * This implementation wraps PDO and provides a consistent interface for all database
 * operations across the foundation layer. It eliminates the need for individual
 * implementations to directly interact with PDO, improving testability and enabling
 * easier addition of features like logging, caching, or query profiling.
 */
class DatabaseRepository implements DatabaseRepositoryContract
{
    private PDO $pdo;

    /**
     * Initialize the repository with a PDO connection.
     *
     * @param PDO $pdo The PDO database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        // Set error mode to throw exceptions for better error handling
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Prepare a SQL statement for execution.
     *
     * @param string $sql The SQL statement to prepare
     * @return \PDOStatement A prepared statement object
     */
    public function prepare(string $sql)
    {
        return $this->pdo->prepare($sql);
    }

    /**
     * Execute a prepared statement with the given parameters.
     *
     * @param mixed $stmt The prepared statement
     * @param array $params The parameters to bind
     * @return bool True if successful, false otherwise
     */
    public function execute($stmt, array $params = []): bool
    {
        try {
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            // Log or handle the error as needed
            throw $e;
        }
    }

    /**
     * Fetch a single row from a statement result.
     *
     * @param mixed $stmt The prepared statement that has been executed
     * @param int $fetchMode The fetch mode (default PDO::FETCH_ASSOC)
     * @return array|null The row as an associative array, or null if no row found
     */
    public function fetch($stmt, int $fetchMode = PDO::FETCH_ASSOC): ?array
    {
        $result = $stmt->fetch($fetchMode);
        return $result ?: null;
    }

    /**
     * Fetch all rows from a statement result.
     *
     * @param mixed $stmt The prepared statement that has been executed
     * @param int $fetchMode The fetch mode (default PDO::FETCH_ASSOC)
     * @return array An array of rows
     */
    public function fetchAll($stmt, int $fetchMode = PDO::FETCH_ASSOC): array
    {
        return $stmt->fetchAll($fetchMode) ?: [];
    }

    /**
     * Get the number of rows affected by the last statement.
     *
     * @param mixed $stmt The prepared statement
     * @return int The number of affected rows
     */
    public function rowCount($stmt): int
    {
        return $stmt->rowCount();
    }

    /**
     * Begin a database transaction.
     *
     * @return bool True if successful
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction.
     *
     * @return bool True if successful
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback the current transaction.
     *
     * @return bool True if successful
     */
    public function rollback(): bool
    {
        return $this->pdo->rollback();
    }

    /**
     * Get the last inserted ID.
     *
     * @param string $name Optional sequence name for some databases
     * @return string The last inserted ID
     */
    public function lastInsertId(string $name = ''): string
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * Get the underlying PDO connection for advanced operations.
     *
     * This should be used sparingly - prefer using the repository methods.
     *
     * @return PDO The PDO connection
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }
}
