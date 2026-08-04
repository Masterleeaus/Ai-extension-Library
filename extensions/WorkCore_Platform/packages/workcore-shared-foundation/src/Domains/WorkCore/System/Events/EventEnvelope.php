<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Events;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class EventEnvelope implements EventEnvelopeContract
{
    private string $eventId;
    private DateTimeImmutable $occurredAt;
    private string $correlationId;
    private string $idempotencyKey;

    public function __construct(
        private string $eventType,
        private int $schemaVersion,
        private int $tenantId,
        private string $aggregateType,
        private string $aggregateId,
        private array $payload,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredAt = null,
        ?int $actorId = null,
        ?string $correlationId = null,
        ?string $causationId = null,
        ?string $idempotencyKey = null,
        private array $metadata = [],
    ) {
        $this->eventId = $eventId ?? Uuid::uuid4()->toString();
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
        $this->correlationId = $correlationId ?? Uuid::uuid4()->toString();
        $this->idempotencyKey = $idempotencyKey ?? $this->eventId;
        $this->actorId = $actorId;
        $this->causationId = $causationId;
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function schemaVersion(): int
    {
        return $this->schemaVersion;
    }

    public function tenantId(): int
    {
        return $this->tenantId;
    }

    public function aggregateType(): string
    {
        return $this->aggregateType;
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function actorId(): ?int
    {
        return $this->actorId ?? null;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function causationId(): ?string
    {
        return $this->causationId;
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public static function from(array $data): self
    {
        return new self(
            eventType: $data['eventType'] ?? $data['event_type'] ?? throw new \InvalidArgumentException('eventType is required'),
            schemaVersion: $data['schemaVersion'] ?? $data['schema_version'] ?? 1,
            tenantId: $data['tenantId'] ?? $data['tenant_id'] ?? throw new \InvalidArgumentException('tenantId is required'),
            aggregateType: $data['aggregateType'] ?? $data['aggregate_type'] ?? throw new \InvalidArgumentException('aggregateType is required'),
            aggregateId: $data['aggregateId'] ?? $data['aggregate_id'] ?? throw new \InvalidArgumentException('aggregateId is required'),
            payload: $data['payload'] ?? [],
            eventId: $data['eventId'] ?? $data['event_id'],
            occurredAt: isset($data['occurredAt']) ? new DateTimeImmutable($data['occurredAt']) : (isset($data['occurred_at']) ? new DateTimeImmutable($data['occurred_at']) : null),
            actorId: $data['actorId'] ?? $data['actor_id'],
            correlationId: $data['correlationId'] ?? $data['correlation_id'],
            causationId: $data['causationId'] ?? $data['causation_id'],
            idempotencyKey: $data['idempotencyKey'] ?? $data['idempotency_key'],
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'eventId' => $this->eventId,
            'eventType' => $this->eventType,
            'schemaVersion' => $this->schemaVersion,
            'tenantId' => $this->tenantId,
            'aggregateType' => $this->aggregateType,
            'aggregateId' => $this->aggregateId,
            'occurredAt' => $this->occurredAt->format('c'),
            'actorId' => $this->actorId,
            'correlationId' => $this->correlationId,
            'causationId' => $this->causationId,
            'idempotencyKey' => $this->idempotencyKey,
            'payload' => $this->payload,
            'metadata' => $this->metadata,
        ];
    }

    private ?int $actorId;
    private ?string $causationId;
}
