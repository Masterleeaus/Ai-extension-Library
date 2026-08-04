<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface ConnectorRuntimeContract
{
    public function register(
        string $provider,
        string $className,
        array $config = []
    ): bool;

    public function connect(
        string $tenantId,
        string $provider,
        array $credentials
    ): string;

    public function disconnect(string $connectionId): bool;

    public function execute(
        string $connectionId,
        string $action,
        array $params = []
    ): array;

    public function getStatus(string $connectionId): array;

    public function listConnectors(string $tenantId): array;
}
