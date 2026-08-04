<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Integration;

interface WorkCoreModuleIntegrationContract
{
    public function moduleName(): string;

    public function extensionName(): string;

    public function canQuery(string $operationType): bool;

    public function canMutate(string $operationType): bool;

    public function query(string $operation, array $parameters = []): array;

    public function mutate(string $operation, array $data = []): array;

    public function getAvailableOperations(): array;

    public function validatePermissions(string $operation, array $context): bool;

    public function enrichContextWithModuleData(array $context): array;
}
