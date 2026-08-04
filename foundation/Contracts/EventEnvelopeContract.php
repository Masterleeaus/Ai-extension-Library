<?php

declare(strict_types=1);

namespace Foundation\Contracts;

use DateTime;

interface EventEnvelopeContract
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getEventType(): string;

    public function getVersion(): int;

    public function getPayload(): array;

    public function getTimestamp(): DateTime;

    public function getCorrelationId(): ?string;

    public function getCausationId(): ?string;

    public function getMetadata(): array;

    public function getProcessedAt(): ?DateTime;

    public function markProcessed(DateTime $timestamp): void;

    public function isProcessed(): bool;

    public function isIdempotent(): bool;

    public function toArray(): array;

    public static function from(array $data): self;
}
