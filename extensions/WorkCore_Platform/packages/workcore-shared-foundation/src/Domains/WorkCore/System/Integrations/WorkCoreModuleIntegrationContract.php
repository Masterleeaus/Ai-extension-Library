<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Integrations;

interface WorkCoreModuleIntegrationContract
{
    public function moduleName(): string;
    public function extensionName(): string;
    public function canQuery(): bool;
    public function canMutate(): bool;
    public function query(string $operation, array $params = []): array;
    public function mutate(string $operation, array $params = []): array;
    public function getAvailableOperations(): array;
    public function validatePermissions(int $tenantId, int $actorId, string $operation): bool;
    public function enrichContextWithModuleData(array $context): array;
}
