<?php

declare(strict_types=1);

namespace Foundation\Contracts;

/**
 * DatabaseRepositoryContract provides an abstraction layer for database operations.
 *
 * This contract enables dependency injection of database functionality and provides
 * a consistent interface for all implementations to use instead of directly coupling
 * to PDO. This improves testability, enables mocking for tests, and allows for easier
 * refactoring and addition of features like caching or logging.
 */
interface DatabaseRepositoryContract
{
    /**
     * Prepare a SQL statement for execution.
     *
     * @param string $sql The SQL statement to prepare
     * @return mixed A prepared statement object
     */
    public function prepare(string $sql);

    /**
     * Execute a prepared statement with the given parameters.
     *
     * @param mixed $stmt The prepared statement
     * @param array $params The parameters to bind
     * @return bool True if successful, false otherwise
     */
    public function execute($stmt, array $params = []): bool;

    /**
     * Fetch a single row from a statement result.
     *
     * @param mixed $stmt The prepared statement that has been executed
     * @param int $fetchMode The fetch mode (default PDO::FETCH_ASSOC)
     * @return array|null The row as an associative array, or null if no row found
     */
    public function fetch($stmt, int $fetchMode = \PDO::FETCH_ASSOC): ?array;

    /**
     * Fetch all rows from a statement result.
     *
     * @param mixed $stmt The prepared statement that has been executed
     * @param int $fetchMode The fetch mode (default PDO::FETCH_ASSOC)
     * @return array An array of rows
     */
    public function fetchAll($stmt, int $fetchMode = \PDO::FETCH_ASSOC): array;

    /**
     * Get the number of rows affected by the last statement.
     *
     * @param mixed $stmt The prepared statement
     * @return int The number of affected rows
     */
    public function rowCount($stmt): int;

    /**
     * Begin a database transaction.
     *
     * @return bool True if successful
     */
    public function beginTransaction(): bool;

    /**
     * Commit the current transaction.
     *
     * @return bool True if successful
     */
    public function commit(): bool;

    /**
     * Rollback the current transaction.
     *
     * @return bool True if successful
     */
    public function rollback(): bool;

    /**
     * Get the last inserted ID.
     *
     * @param string $name Optional sequence name for some databases
     * @return string The last inserted ID
     */
    public function lastInsertId(string $name = ''): string;

    /**
     * Get the underlying PDO connection for advanced operations.
     *
     * This should be used sparingly - prefer using the repository methods.
     *
     * @return \PDO The PDO connection
     */
    public function getPDO(): \PDO;
}
