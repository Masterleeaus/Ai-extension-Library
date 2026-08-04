<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface ExtensionLifecycleContract
{
    public function registerExtension(
        string $tenantId,
        string $extensionName,
        array $extensionMetadata
    ): string;

    public function getExtensionStatus(
        string $tenantId,
        string $extensionId
    ): ?array;

    public function publishExtensionVersion(
        string $tenantId,
        string $extensionId,
        string $version,
        array $releaseNotes
    ): bool;

    public function enableExtension(
        string $tenantId,
        string $extensionId
    ): bool;

    public function disableExtension(
        string $tenantId,
        string $extensionId,
        string $reason
    ): bool;

    public function grantExtensionPass(
        string $tenantId,
        string $extensionId,
        string $passType,
        int $durationDays
    ): string;

    public function validateExtensionPass(
        string $tenantId,
        string $passId
    ): bool;

    public function listExtensions(
        string $tenantId,
        ?string $status = null
    ): array;
}
