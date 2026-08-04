<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface MediaQuarantineContract
{
    public function quarantine(
        string $tenantId,
        string $mediaId,
        string $mediaType,
        string $sourcePath,
        array $metadata = []
    ): string;

    public function scan(
        string $tenantId,
        string $quarantineId
    ): array;

    public function release(
        string $tenantId,
        string $quarantineId,
        string $destination
    ): bool;

    public function reject(
        string $tenantId,
        string $quarantineId,
        string $reason
    ): bool;

    public function getStatus(
        string $tenantId,
        string $quarantineId
    ): ?array;

    public function listQuarantined(
        string $tenantId,
        array $filters = []
    ): array;

    public function purgeExpired(string $tenantId): int;
}
