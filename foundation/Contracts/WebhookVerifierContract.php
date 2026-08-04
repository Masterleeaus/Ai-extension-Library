<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface WebhookVerifierContract
{
    public function register(
        string $provider,
        string $secret,
        ?string $algorithm = 'sha256'
    ): bool;

    public function verify(
        string $provider,
        string $payload,
        string $signature,
        ?array $headers = null
    ): bool;

    public function checkReplay(
        string $provider,
        string $eventId,
        int $maxAgeSeconds = 300
    ): bool;

    public function recordEvent(
        string $provider,
        string $eventId,
        int $timestamp
    ): void;

    public function resolveTenant(
        string $provider,
        array $payload,
        ?array $headers = null
    ): ?string;
}
