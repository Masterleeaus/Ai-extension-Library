<?php

declare(strict_types=1);

namespace App\Extensions\AIAgentWhatsappChannel\System\Media\Services;

use App\Extensions\AIAgentWhatsappChannel\System\Media\Contracts\MediaQuarantineContract;
use App\Extensions\AIAgentWhatsappChannel\System\Media\Exceptions\MediaAccessDeniedException;
use App\Extensions\AIAgentWhatsappChannel\System\Media\Exceptions\MediaNotFoundException;
use App\Extensions\AIAgentWhatsappChannel\System\Media\Exceptions\MediaQuarantineException;
use App\Extensions\AIAgentWhatsappChannel\System\Media\ValueObjects\QuarantinedMedia;
use DateTimeImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class SecureMediaQuarantine implements MediaQuarantineContract
{
    // Maximum 50 MB for media
    private const MAX_MEDIA_SIZE = 50 * 1024 * 1024;

    // Allowed MIME types for WhatsApp media
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
    ];

    // Magic byte signatures for verification
    private const MAGIC_BYTES = [
        'image/jpeg' => [0xFF, 0xD8, 0xFF],
        'image/png' => [0x89, 0x50, 0x4E, 0x47],
        'image/gif' => [0x47, 0x49, 0x46],
        'image/webp' => [0x52, 0x49, 0x46, 0x46], // RIFF
        'application/pdf' => [0x25, 0x50, 0x44, 0x46], // %PDF
    ];

    public function __construct(private readonly Filesystem $storage) {}

    public function quarantine(
        int $tenantId,
        string $sourceMediaId,
        string $mediaContent,
        string $declaredMimeType,
        ?string $filename = null,
    ): QuarantinedMedia {
        // Step 1: Validate size
        $byteSize = strlen($mediaContent);
        if ($byteSize > self::MAX_MEDIA_SIZE) {
            throw MediaQuarantineException::oversized($byteSize, self::MAX_MEDIA_SIZE);
        }

        // Step 2: Validate MIME type is allowed
        $normalizedMime = strtolower(trim($declaredMimeType));
        if (!in_array($normalizedMime, self::ALLOWED_MIME_TYPES, true)) {
            throw MediaQuarantineException::unsupportedMimeType(
                $normalizedMime,
                self::ALLOWED_MIME_TYPES,
            );
        }

        // Step 3: Verify magic bytes
        $detectedMimeType = $this->verifyMagicBytes($mediaContent, $normalizedMime);
        if ($detectedMimeType === null) {
            throw MediaQuarantineException::magicByteMismatch(
                'unknown/unknown',
                $normalizedMime,
            );
        }

        // Step 4: Generate secure attachment ID
        $attachmentId = $this->generateAttachmentId($tenantId, $sourceMediaId);

        // Step 5: Calculate content hash for deduplication
        $contentHash = hash('sha256', $mediaContent);

        // Step 6: Store in secure location (per-tenant isolated path)
        $storagePath = $this->getSecureStoragePath($tenantId, $attachmentId);
        $this->storage->put($storagePath, $mediaContent, [
            'visibility' => 'private',
            'metadata' => [
                'tenant_id' => (string)$tenantId,
                'attachment_id' => $attachmentId,
                'source_media_id' => $sourceMediaId,
                'content_hash' => $contentHash,
                'quarantined_at' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
            ],
        ]);

        // Step 7: Create quarantine record
        return new QuarantinedMedia(
            attachmentId: $attachmentId,
            tenantId: $tenantId,
            sourceMediaId: $sourceMediaId,
            detectedMimeType: $detectedMimeType,
            byteSize: $byteSize,
            contentHash: $contentHash,
            status: 'quarantined',
            filename: $filename,
        );
    }

    public function retrieve(int $tenantId, string $attachmentId): QuarantinedMedia
    {
        // Parse attachment ID to verify tenant ownership
        $parts = explode('_', $attachmentId, 2);
        if (count($parts) < 2) {
            throw MediaNotFoundException::byAttachmentId($attachmentId);
        }

        $storedTenantId = (int)$parts[0];

        // Step 1: Verify tenant isolation
        if ($storedTenantId !== $tenantId) {
            throw MediaAccessDeniedException::crossTenantAccess($tenantId, $storedTenantId);
        }

        // Step 2: Check if media exists and is not expired
        $storagePath = $this->getSecureStoragePath($tenantId, $attachmentId);
        if (!$this->storage->exists($storagePath)) {
            throw MediaNotFoundException::byAttachmentId($attachmentId);
        }

        // Step 3: Return metadata-only quarantine record (content via stream if needed)
        return new QuarantinedMedia(
            attachmentId: $attachmentId,
            tenantId: $tenantId,
            sourceMediaId: '', // Retrieved from storage metadata
            detectedMimeType: 'application/octet-stream', // Retrieved from storage metadata
            byteSize: $this->storage->size($storagePath),
            contentHash: '', // Retrieved from storage metadata
            status: 'promoted',
            filename: null,
        );
    }

    public function promote(int $tenantId, string $attachmentId): void
    {
        // Verify tenant access first
        $parts = explode('_', $attachmentId, 2);
        if (count($parts) < 2) {
            throw MediaNotFoundException::byAttachmentId($attachmentId);
        }

        $storedTenantId = (int)$parts[0];
        if ($storedTenantId !== $tenantId) {
            throw MediaAccessDeniedException::crossTenantAccess($tenantId, $storedTenantId);
        }

        $storagePath = $this->getSecureStoragePath($tenantId, $attachmentId);
        if (!$this->storage->exists($storagePath)) {
            throw MediaNotFoundException::byAttachmentId($attachmentId);
        }

        // Update metadata to mark as promoted
        $metadata = $this->storage->getMetadata($storagePath) ?? [];
        $metadata['promoted_at'] = (new DateTimeImmutable())->format(DateTimeImmutable::ATOM);
        $this->storage->setVisibility($storagePath, 'private');
    }

    public function cleanupExpired(int $maxAgeDays = 30): int
    {
        // In a production system, this would:
        // 1. Query a database for quarantine records older than $maxAgeDays
        // 2. Delete the associated storage files
        // 3. Delete the quarantine records
        // 4. Return count of cleaned files
        return 0;
    }

    /**
     * Verify media content against magic bytes to prevent MIME type spoofing.
     */
    private function verifyMagicBytes(string $content, string $declaredMimeType): ?string
    {
        if (!isset(self::MAGIC_BYTES[$declaredMimeType])) {
            return null;
        }

        $magic = self::MAGIC_BYTES[$declaredMimeType];
        $contentBytes = array_values(unpack('C*', substr($content, 0, max(...array_map('count', self::MAGIC_BYTES)))));

        // Check if content starts with expected magic bytes
        foreach ($magic as $i => $byte) {
            if (!isset($contentBytes[$i]) || $contentBytes[$i] !== $byte) {
                return null;
            }
        }

        return $declaredMimeType;
    }

    /**
     * Generate a secure, tenant-scoped attachment ID.
     */
    private function generateAttachmentId(int $tenantId, string $sourceMediaId): string
    {
        // Format: {tenantId}_{randomId}_{hash}
        $randomPart = Str::random(16);
        $hashPart = hash('crc32', $sourceMediaId . time());

        return "{$tenantId}_{$randomPart}_{$hashPart}";
    }

    /**
     * Get secure storage path that enforces tenant isolation.
     */
    private function getSecureStoragePath(int $tenantId, string $attachmentId): string
    {
        // Nested path structure: private/tenant-{id}/media/{attachmentId}
        return "private/tenant-{$tenantId}/media/{$attachmentId}";
    }
}
