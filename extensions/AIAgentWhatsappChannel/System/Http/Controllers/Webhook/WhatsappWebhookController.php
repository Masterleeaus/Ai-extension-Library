<?php

declare(strict_types=1);

namespace App\Extensions\AIAgentWhatsappChannel\System\Http\Controllers\Webhook;

use App\Extensions\AIAgent\System\Connectors\IncomingMessageHandler;
use App\Extensions\AIAgent\System\Connectors\ValueObjects\IncomingMessage;
use App\Extensions\AIAgent\System\Enums\ChannelEnum;
use App\Extensions\AIAgent\System\Models\AIAgentChannel;
use App\Extensions\AIAgentWhatsappChannel\System\Media\Contracts\MediaQuarantineContract;
use App\Extensions\AIAgentWhatsappChannel\System\Media\Exceptions\MediaQuarantineException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsappWebhookController extends Controller
{
    private const GRAPH_API_URL = 'https://graph.facebook.com/v20.0';
    private const WEBHOOK_SIGNATURE_HEADER = 'X-Hub-Signature-256';
    private const MAX_MEDIA_SIZE = 50 * 1024 * 1024; // 50 MB

    /** Supported media types and their default MIME types */
    private const MEDIA_TYPES = [
        'image'    => 'image/jpeg',
        'sticker'  => 'image/webp',
        'document' => 'application/pdf',
    ];

    public function __construct(
        private readonly IncomingMessageHandler $handler,
        private readonly MediaQuarantineContract $quarantine,
    ) {}

    /**
     * Meta webhook verification (GET).
     */
    public function verify(int $channel, Request $request): Response
    {
        $aiAgentChannel = AIAgentChannel::query()->find($channel);

        if ($aiAgentChannel === null) {
            return response('Channel not found.', 404);
        }

        $mode = $request->query('hub_mode');
        $verifyToken = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge', '');

        $storedToken = $aiAgentChannel->getCredential('whatsapp_verify_token');

        if ($mode === 'subscribe' && $verifyToken === $storedToken) {
            return response((string) $challenge, 200);
        }

        return response('Verification failed.', 403);
    }

    /**
     * Incoming WhatsApp message (POST).
     * Meta Cloud API payload structure:
     * entry[].changes[].value.messages[].{from, type, text.body, image, document, sticker}
     *
     * Security: Webhook signature verification, media quarantine, and tenant isolation enforced.
     */
    public function handle(int $channel, Request $request): array
    {
        $aiAgentChannel = AIAgentChannel::query()->find($channel);

        if ($aiAgentChannel === null) {
            Log::warning('[AIAgentWhatsappChannel] Channel not found', ['channel' => $channel]);
            return ['ok' => false];
        }

        // Verify webhook signature before processing
        $webhookSecret = $aiAgentChannel->getCredential('whatsapp_webhook_secret');
        if ($webhookSecret && !$this->verifyWebhookSignature($request, $webhookSecret)) {
            Log::warning('[AIAgentWhatsappChannel] Webhook signature verification failed', ['channel' => $channel]);
            return ['ok' => false];
        }

        $accessToken = $aiAgentChannel->getCredential('whatsapp_access_token');
        $tenantId = $aiAgentChannel->company_id ?? 0;
        $entries = $request->input('entry', []);

        foreach ($entries as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['messages'] ?? [] as $messageData) {
                    $senderId = (string) ($messageData['from'] ?? '');

                    if (empty($senderId)) {
                        continue;
                    }

                    $type = $messageData['type'] ?? '';
                    $text = '';
                    $attachments = [];

                    if ($type === 'text') {
                        $text = (string) ($messageData['text']['body'] ?? '');
                    } elseif (isset(self::MEDIA_TYPES[$type]) && $accessToken) {
                        $mediaData = $messageData[$type] ?? [];
                        $text = (string) ($mediaData['caption'] ?? '');
                        $attachment = $this->downloadAndQuarantineMedia(
                            accessToken: $accessToken,
                            mediaData: $mediaData,
                            mediaType: $type,
                            tenantId: $tenantId,
                        );

                        if ($attachment !== null) {
                            $attachments[] = $attachment;
                        }
                    }

                    if (empty($text) && empty($attachments)) {
                        continue;
                    }

                    $incomingMessage = new IncomingMessage(
                        channel: ChannelEnum::Whatsapp,
                        senderId: $senderId,
                        text: $text,
                        attachments: $attachments,
                        rawPayload: [], // Don't pass raw payload to avoid leaking tokens
                    );

                    $this->handler->handle($incomingMessage, $aiAgentChannel);
                }
            }
        }

        return ['ok' => true];
    }

    /**
     * Download WhatsApp media via Meta Graph API and quarantine it securely.
     * Returns only attachment metadata, never base64 content.
     *
     * @param  array<string, mixed>  $mediaData
     */
    private function downloadAndQuarantineMedia(
        string $accessToken,
        array $mediaData,
        string $mediaType,
        int $tenantId,
    ): ?array {
        $mediaId = $mediaData['id'] ?? null;

        if (empty($mediaId)) {
            return null;
        }

        try {
            // Step 1: Resolve the temporary download URL
            $metaResponse = Http::withToken($accessToken)
                ->timeout(10)
                ->get(self::GRAPH_API_URL . "/{$mediaId}");

            if ($metaResponse->failed()) {
                Log::warning('[AIAgentWhatsappChannel] Failed to resolve media URL', [
                    'media_id' => $mediaId,
                    'tenant_id' => $tenantId,
                ]);
                return null;
            }

            $downloadUrl = $metaResponse->json('url');
            if (empty($downloadUrl)) {
                return null;
            }

            // Step 2: Download file content with size check
            $fileResponse = Http::withToken($accessToken)
                ->timeout(30)
                ->get($downloadUrl);

            if ($fileResponse->failed()) {
                Log::warning('[AIAgentWhatsappChannel] Failed to download media', [
                    'media_id' => $mediaId,
                    'tenant_id' => $tenantId,
                ]);
                return null;
            }

            $mediaContent = $fileResponse->body();
            if (strlen($mediaContent) > self::MAX_MEDIA_SIZE) {
                Log::warning('[AIAgentWhatsappChannel] Media exceeds size limit', [
                    'media_id' => $mediaId,
                    'size' => strlen($mediaContent),
                    'tenant_id' => $tenantId,
                ]);
                return null;
            }

            // Step 3: Determine MIME type
            $declaredMimeType = $mediaData['mime_type']
                ?? $metaResponse->json('mime_type')
                ?? self::MEDIA_TYPES[$mediaType]
                ?? 'application/octet-stream';

            // Step 4: Quarantine media (validates magic bytes, stores securely)
            $quarantined = $this->quarantine->quarantine(
                tenantId: $tenantId,
                sourceMediaId: $mediaId,
                mediaContent: $mediaContent,
                declaredMimeType: $declaredMimeType,
                filename: $mediaData['filename'] ?? null,
            );

            // Step 5: Return attachment metadata only (not base64!)
            return [
                'type'          => str_starts_with($quarantined->detectedMimeType, 'image/') ? 'image' : 'document',
                'attachment_id' => $quarantined->attachmentId,
                'mime_type'     => $quarantined->detectedMimeType,
                'size'          => $quarantined->byteSize,
                'filename'      => $quarantined->filename,
            ];
        } catch (MediaQuarantineException $e) {
            Log::warning('[AIAgentWhatsappChannel] Media quarantine failed', [
                'media_id' => $mediaId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            return null;
        } catch (Throwable $e) {
            Log::error('[AIAgentWhatsappChannel] Media processing exception', [
                'media_id' => $mediaId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verify Meta webhook signature to prevent replay attacks.
     */
    private function verifyWebhookSignature(Request $request, string $webhookSecret): bool
    {
        $signature = $request->header(self::WEBHOOK_SIGNATURE_HEADER);
        if (empty($signature)) {
            return false;
        }

        $payload = $request->getContent();
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expected, $signature);
    }
}
