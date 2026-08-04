<?php

declare(strict_types=1);

namespace App\Extensions\AIAgentWhatsappChannel\System\Media\ValueObjects;

use DateTimeImmutable;

final readonly class QuarantinedMedia
{
    public function __construct(
        public string $attachmentId,
        public int $tenantId,
        public string $sourceMediaId,
        public string $detectedMimeType,
        public int $byteSize,
        public string $contentHash,
        public string $status, // 'quarantined', 'scanned', 'promoted', 'rejected'
        public ?string $filename = null,
        public ?string $scanResult = null,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
        public ?DateTimeImmutable $promotedAt = null,
    ) {}

    public function isPromoted(): bool
    {
        return $this->status === 'promoted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->status === 'quarantined' || $this->status === 'scanned';
    }

    public function toMetadata(): array
    {
        return [
            'attachment_id' => $this->attachmentId,
            'mime_type' => $this->detectedMimeType,
            'size' => $this->byteSize,
            'filename' => $this->filename,
            'status' => $this->status,
            'created_at' => $this->createdAt->format(DateTimeImmutable::ATOM),
        ];
    }
}
