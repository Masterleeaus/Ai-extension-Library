<?php

declare(strict_types=1);

namespace Foundation\Contracts;

/**
 * DatabaseRepositoryContract provides an abstraction layer for database operations.
 */
interface DatabaseRepositoryContract
{
    public function prepare(string $sql);
    public function execute($stmt, array $params = []): bool;
    public function fetch($stmt, int $fetchMode = \PDO::FETCH_ASSOC): ?array;
    public function fetchAll($stmt, int $fetchMode = \PDO::FETCH_ASSOC): array;
    public function rowCount($stmt): int;
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollback(): bool;
    public function lastInsertId(string $name = ''): string;
    public function getPDO(): \PDO;
}
