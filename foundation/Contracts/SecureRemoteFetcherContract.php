<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface SecureRemoteFetcherContract
{
    public function fetch(
        string $tenantId,
        string $url,
        array $options = []
    ): array;

    public function validateUrl(string $url): bool;

    public function isAllowedDomain(string $domain): bool;

    public function setRateLimit(
        string $tenantId,
        int $requestsPerMinute
    ): bool;

    public function getRateLimit(string $tenantId): ?int;

    public function recordFetch(
        string $tenantId,
        string $url,
        int $statusCode,
        int $bytesTransferred,
        float $duration
    ): void;

    public function getBlockedDomains(): array;

    public function addBlockedDomain(string $domain): bool;

    public function removeBlockedDomain(string $domain): bool;
}
