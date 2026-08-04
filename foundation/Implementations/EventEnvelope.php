<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\EventEnvelopeContract;
use DateTime;
use RuntimeException;

class EventEnvelope implements EventEnvelopeContract
{
    private string $id;
    private string $tenantId;
    private string $eventType;
    private int $version;
    private array $payload;
    private DateTime $timestamp;
    private ?string $correlationId;
    private ?string $causationId;
    private array $metadata;
    private ?DateTime $processedAt = null;

    public function __construct(
        string $id,
        string $tenantId,
        string $eventType,
        int $version,
        array $payload,
        DateTime $timestamp,
        ?string $correlationId = null,
        ?string $causationId = null,
        array $metadata = []
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->eventType = $eventType;
        $this->version = $version;
        $this->payload = $payload;
        $this->timestamp = $timestamp;
        $this->correlationId = $correlationId;
        $this->causationId = $causationId;
        $this->metadata = $metadata;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function getCausationId(): ?string
    {
        return $this->causationId;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getProcessedAt(): ?DateTime
    {
        return $this->processedAt;
    }

    public function markProcessed(DateTime $timestamp): void
    {
        $this->processedAt = $timestamp;
    }

    public function isProcessed(): bool
    {
        return $this->processedAt !== null;
    }

    public function isIdempotent(): bool
    {
        return !empty($this->id) && !empty($this->correlationId);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'event_type' => $this->eventType,
            'version' => $this->version,
            'payload' => $this->payload,
            'timestamp' => $this->timestamp->toIso8601String(),
            'correlation_id' => $this->correlationId,
            'causation_id' => $this->causationId,
            'metadata' => $this->metadata,
            'processed_at' => $this->processedAt?->toIso8601String(),
        ];
    }

    public static function from(array $data): self
    {
        return new self(
            $data['id'] ?? throw new RuntimeException('Missing event id'),
            $data['tenant_id'] ?? throw new RuntimeException('Missing tenant_id'),
            $data['event_type'] ?? throw new RuntimeException('Missing event_type'),
            $data['version'] ?? 1,
            $data['payload'] ?? [],
            new DateTime($data['timestamp'] ?? 'now'),
            $data['correlation_id'] ?? null,
            $data['causation_id'] ?? null,
            $data['metadata'] ?? []
        );
    }
}
