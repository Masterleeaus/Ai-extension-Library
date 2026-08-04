<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Events;

interface EventEnvelopeContract
{
    public function eventId(): string;

    public function eventType(): string;

    public function schemaVersion(): int;

    public function tenantId(): int;

    public function aggregateType(): string;

    public function aggregateId(): string;

    public function occurredAt(): \DateTimeImmutable;

    public function actorId(): ?int;

    public function correlationId(): string;

    public function causationId(): ?string;

    public function idempotencyKey(): string;

    public function payload(): array;

    public function metadata(): array;
}
