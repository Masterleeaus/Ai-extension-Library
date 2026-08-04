<?php

declare(strict_types=1);

namespace App\Extensions\AIAgentWhatsappChannel\System\Media\Contracts;

use App\Extensions\AIAgentWhatsappChannel\System\Media\ValueObjects\QuarantinedMedia;

interface MediaQuarantineContract
{
    /**
     * Store media in quarantine with validation and content scanning.
     *
     * @param int $tenantId
     * @param string $sourceMediaId Meta media ID
     * @param string $mediaContent Binary content
     * @param string $declaredMimeType MIME type from source
     * @param string|null $filename Original filename
     * @return QuarantinedMedia Quarantine record with attachment ID
     * @throws MediaQuarantineException On validation/scan failure
     */
    public function quarantine(
        int $tenantId,
        string $sourceMediaId,
        string $mediaContent,
        string $declaredMimeType,
        ?string $filename = null,
    ): QuarantinedMedia;

    /**
     * Retrieve quarantined media by attachment ID after validation.
     *
     * @param int $tenantId
     * @param string $attachmentId Server-generated attachment ID
     * @return QuarantinedMedia With content stream/path
     * @throws MediaAccessDeniedException Cross-tenant access attempt
     * @throws MediaNotFoundException Attachment not found or expired
     */
    public function retrieve(int $tenantId, string $attachmentId): QuarantinedMedia;

    /**
     * Mark media as promoted (passed all scans, safe to use).
     */
    public function promote(int $tenantId, string $attachmentId): void;

    /**
     * Clean up expired media records and associated storage.
     */
    public function cleanupExpired(int $maxAgeDays = 30): int;
}
