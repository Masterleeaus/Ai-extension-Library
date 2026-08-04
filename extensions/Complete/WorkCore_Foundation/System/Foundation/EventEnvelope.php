<?php

namespace Extensions\WorkCore_Foundation\System\Foundation;

use Ramsey\Uuid\Uuid;

/**
 * Issue #144: EventEnvelope & Idempotent Event Consumers
 * Provides idempotent event processing with deduplication
 */
class EventEnvelope
{
    protected $eventId;
    protected $idempotencyKey;
    protected $tenantId;
    protected $userId;
    protected $eventType;
    protected $payload;
    protected $timestamp;
    protected $correlationId;
    protected $causationId;
    protected $metadata = [];
    protected $version = 1;

    public function __construct(
        string $eventType,
        array $payload,
        string $tenantId,
        string $idempotencyKey
    ) {
        $this->eventId = (string)Uuid::uuid4();
        $this->eventType = $eventType;
        $this->payload = $payload;
        $this->tenantId = $tenantId;
        $this->idempotencyKey = $idempotencyKey;
        $this->timestamp = now();
        $this->correlationId = $idempotencyKey;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function withCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;
        return $this;
    }

    public function getCausationId(): ?string
    {
        return $this->causationId ?? null;
    }

    public function withCausationId(string $causationId): self
    {
        $this->causationId = $causationId;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function withMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'idempotency_key' => $this->idempotencyKey,
            'event_type' => $this->eventType,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'payload' => $this->payload,
            'timestamp' => $this->timestamp->toIso8601String(),
            'correlation_id' => $this->correlationId,
            'causation_id' => $this->causationId,
            'metadata' => $this->metadata,
            'version' => $this->version,
        ];
    }

    public static function fromArray(array $data): self
    {
        $envelope = new self(
            $data['event_type'],
            $data['payload'],
            $data['tenant_id'],
            $data['idempotency_key']
        );

        $envelope->eventId = $data['event_id'];
        $envelope->userId = $data['user_id'] ?? null;
        $envelope->timestamp = \DateTime::createFromFormat('c', $data['timestamp']);
        $envelope->correlationId = $data['correlation_id'];
        $envelope->causationId = $data['causation_id'] ?? null;
        $envelope->metadata = $data['metadata'] ?? [];
        $envelope->version = $data['version'] ?? 1;

        return $envelope;
    }
}
