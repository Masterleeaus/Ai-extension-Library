<?php

declare(strict_types=1);

namespace App\Extensions\AIAgentWhatsappChannel\System\Domain\Services;

use App\Extensions\AIAgentWhatsappChannel\System\Domain\Models\MediaAttachment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class SecureMediaDownloader
{
    private const MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB
    private const DOWNLOAD_TIMEOUT = 30;
    private const MAGIC_BYTES = [
        'image/jpeg' => [0xFF, 0xD8, 0xFF],
        'image/png' => [0x89, 0x50, 0x4E, 0x47],
        'image/webp' => [0x52, 0x49, 0x46, 0x46],
        'application/pdf' => [0x25, 0x50, 0x44, 0x46],
        'image/gif' => [0x47, 0x49, 0x46],
    ];

    public function __construct(
        private readonly MediaValidationService $validator,
    ) {}

    public function download(
        int $tenantId,
        int $channelId,
        string $accessToken,
        string $providerId,
        string $claimedMimeType,
        string $filename,
        string $sourceIdentifier,
        string $correlationId,
    ): ?MediaAttachment {
        $mediaId = $providerId;

        try {
            // Step 1: Resolve the temporary download URL
            $metaResponse = Http::withToken($accessToken)
                ->timeout(10)
                ->get("https://graph.facebook.com/v20.0/{$mediaId}");

            if ($metaResponse->failed()) {
                Log::warning('[WhatsApp Media] Failed to resolve media URL', [
                    'media_id' => $mediaId,
                    'correlation_id' => $correlationId,
                ]);

                return null;
            }

            $downloadUrl = $metaResponse->json('url');

            if (empty($downloadUrl)) {
                return null;
            }

            // Step 2: Download with stream and size limits
            $fileContent = '';
            $fileSize = 0;
            $downloadSuccess = false;

            $fileResponse = Http::withToken($accessToken)
                ->timeout(self::DOWNLOAD_TIMEOUT)
                ->asForm()
                ->stream(function ($chunk) use (&$fileContent, &$fileSize, &$downloadSuccess) {
                    $fileSize += strlen($chunk);

                    if ($fileSize > self::MAX_FILE_SIZE) {
                        throw new \RuntimeException('File exceeds maximum size of ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB');
                    }

                    $fileContent .= $chunk;
                }, $downloadUrl);

            if ($fileResponse->failed()) {
                Log::warning('[WhatsApp Media] Failed to download media', [
                    'media_id' => $mediaId,
                    'correlation_id' => $correlationId,
                ]);

                return null;
            }

            // Step 3: Validate MIME type using magic bytes
            $detectedMimeType = $this->validateMagicBytes($fileContent, $claimedMimeType);

            if ($detectedMimeType === null) {
                Log::warning('[WhatsApp Media] MIME type mismatch', [
                    'media_id' => $mediaId,
                    'claimed' => $claimedMimeType,
                    'correlation_id' => $correlationId,
                ]);

                return $this->recordQuarantinedAttachment(
                    $tenantId,
                    $channelId,
                    $mediaId,
                    $sourceIdentifier,
                    $filename,
                    $claimedMimeType,
                    'MIME type validation failed',
                    $fileSize,
                    $correlationId,
                    ['error' => 'MIME type mismatch']
                );
            }

            // Step 4: Store file securely
            $fileHash = hash('sha256', $fileContent);
            $storagePath = $this->storeFileSecurely($tenantId, $channelId, $fileContent, $fileHash);

            // Step 5: Record attachment
            $attachment = new MediaAttachment([
                'tenant_id' => $tenantId,
                'channel_id' => $channelId,
                'provider_media_id' => $mediaId,
                'source_identifier' => $sourceIdentifier,
                'filename' => $filename,
                'mime_type' => $claimedMimeType,
                'detected_mime_type' => $detectedMimeType,
                'file_hash' => $fileHash,
                'file_size' => $fileSize,
                'byte_count' => $fileSize,
                'storage_path' => $storagePath,
                'status' => 'verified',
                'correlation_id' => $correlationId,
                'downloaded_at' => now(),
                'verified_at' => now(),
                'retention_until' => now()->addDays(30),
            ]);

            $attachment->save();

            return $attachment;
        } catch (Throwable $e) {
            Log::error('[WhatsApp Media] Download exception', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return null;
        }
    }

    private function validateMagicBytes(string $fileContent, string $claimedMimeType): ?string
    {
        if (!isset(self::MAGIC_BYTES[$claimedMimeType])) {
            return null;
        }

        $magicBytes = self::MAGIC_BYTES[$claimedMimeType];
        $fileBytes = array_slice(unpack('C*', $fileContent), 0, count($magicBytes));

        if ($fileBytes === $magicBytes) {
            return $claimedMimeType;
        }

        return null;
    }

    private function storeFileSecurely(int $tenantId, int $channelId, string $content, string $hash): string
    {
        $path = "whatsapp/attachments/tenant-{$tenantId}/channel-{$channelId}/{$hash}";

        Storage::disk('private')->put($path, $content);

        return $path;
    }

    private function recordQuarantinedAttachment(
        int $tenantId,
        int $channelId,
        string $mediaId,
        string $sourceIdentifier,
        string $filename,
        string $mimeType,
        string $reason,
        int $fileSize,
        string $correlationId,
        array $errors,
    ): MediaAttachment {
        $attachment = new MediaAttachment([
            'tenant_id' => $tenantId,
            'channel_id' => $channelId,
            'provider_media_id' => $mediaId,
            'source_identifier' => $sourceIdentifier,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'file_hash' => hash('sha256', $correlationId),
            'file_size' => $fileSize,
            'byte_count' => $fileSize,
            'status' => 'quarantined',
            'validation_errors' => array_merge(['reason' => $reason], $errors),
            'correlation_id' => $correlationId,
            'retention_until' => now()->addDays(90),
        ]);

        $attachment->save();

        return $attachment;
    }
}
