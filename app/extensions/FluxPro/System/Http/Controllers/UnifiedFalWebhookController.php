<?php

namespace App\Extensions\FluxPro\System\Http\Controllers;

use App\Extensions\FluxPro\System\Services\FalWebhookVerificationService;
use App\Http\Controllers\Controller;
use App\Models\UserOpenai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UnifiedFalWebhookController extends Controller
{
    public function __construct(
        private readonly FalWebhookVerificationService $verificationService,
    ) {}

    /**
     * Handle FAL webhook callbacks for all providers (FluxPro, NanoBanana, SeeDreamV4)
     *
     * This is the unified endpoint that consolidates all FAL provider webhooks.
     * All providers should route to this single handler.
     *
     * @param string $provider The provider name (flux-pro, nano-banana, seed-dream-v4)
     */
    public function handle(string $provider, Request $request): JsonResponse
    {
        // Validate provider name
        $provider = strtolower($provider);
        if (!in_array($provider, ['flux-pro', 'nano-banana', 'seed-dream-v4'], true)) {
            return response()->json([
                'error' => 'Unknown provider',
            ], 400);
        }

        // Validate payload size and content-type
        $payloadValidation = $this->verificationService->validatePayloadLimits($request);
        if (!$payloadValidation['valid']) {
            return response()->json([
                'error' => 'Payload validation failed',
                'details' => $payloadValidation['errors'],
            ], 400);
        }

        // Verify FAL webhook signature and headers
        $verification = $this->verificationService->verifyWebhook($request);
        if (!$verification['valid']) {
            return response()->json([
                'error' => 'Webhook verification failed',
                'details' => $verification['errors'],
            ], 401);
        }

        $requestId = $verification['headers']['request_id'];
        $userId = $verification['headers']['user_id'];
        $timestamp = $verification['headers']['timestamp'];

        // Check for replay attacks (idempotency)
        if ($this->verificationService->hasRequestBeenProcessed($requestId)) {
            // Already processed - return success to acknowledge
            return response()->json([
                'status' => 'already_processed',
                'request_id' => $requestId,
            ], 200);
        }

        // Extract request_id from payload for matching database records
        // FAL sends the request_id in the payload as well
        $payloadRequestId = data_get($request->json(), 'request_id');
        if (!$payloadRequestId) {
            return response()->json([
                'error' => 'Missing request_id in payload',
            ], 400);
        }

        // Find the generation attempt record
        $openai = UserOpenai::query()
            ->whereIn('response', ['FL', 'NB', 'SDV4']) // FL=FluxPro, NB=NanoBanana, SDV4=SeeDreamV4
            ->where('status', 'IN_QUEUE')
            ->where('request_id', $payloadRequestId)
            ->first();

        if (!$openai) {
            // Don't reveal whether the request exists
            return response()->json([
                'status' => 'processed',
                'request_id' => $requestId,
            ], 200);
        }

        try {
            // Validate provider matches (prevent cross-provider tampering)
            $providerMap = [
                'flux-pro' => 'FL',
                'nano-banana' => 'NB',
                'seed-dream-v4' => 'SDV4',
            ];

            if ($openai->response !== $providerMap[$provider]) {
                return response()->json([
                    'status' => 'processed',
                    'request_id' => $requestId,
                ], 200);
            }

            // Process the webhook callback
            $payload = $request->json()->all();
            $this->processWebhookPayload($openai, $payload, $provider);

            // Mark as processed for idempotency
            $this->verificationService->markRequestAsProcessed($requestId);

            return response()->json([
                'status' => 'processed',
                'request_id' => $requestId,
            ], 200);
        } catch (\Throwable $exception) {
            logger()->error('FAL webhook processing error', [
                'provider' => $provider,
                'request_id' => $requestId,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'request_id' => $requestId,
                'message' => 'Internal processing error',
            ], 500);
        }
    }

    /**
     * Process verified webhook payload
     */
    private function processWebhookPayload(UserOpenai $openai, array $payload, string $provider): void
    {
        // Validate the payload matches the expected event type
        $status = data_get($payload, 'status');
        if ($status !== 'completed') {
            // We only care about completed events for now
            return;
        }

        // Validate result URL exists
        $resultUrl = data_get($payload, 'result.image.url') ??
                     data_get($payload, 'result.images.0.url') ??
                     data_get($payload, 'result.url');

        if (!$resultUrl) {
            logger()->warning('FAL webhook missing result URL', [
                'provider' => $provider,
                'request_id' => $openai->request_id,
            ]);
            return;
        }

        // Fetch the generated image
        $image = $this->downloadImageToStorage($resultUrl);

        // Update the generation attempt record
        $openai->update([
            'output' => $image ?? $openai->output,
            'payload' => $payload,
            'status' => 'COMPLETED',
            'completed_at' => now(),
        ]);

        logger()->info('FAL webhook processed successfully', [
            'provider' => $provider,
            'request_id' => $openai->request_id,
            'image_downloaded' => !is_null($image),
        ]);
    }

    /**
     * Download image from verified URL
     */
    private function downloadImageToStorage(string $url): ?string
    {
        try {
            // Validate URL is HTTPS (FAL only provides HTTPS URLs)
            if (!str_starts_with($url, 'https://')) {
                logger()->warning('FAL image URL not HTTPS', ['url' => $url]);
                return null;
            }

            // Download image
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($url);

            if (!$response->successful()) {
                return null;
            }

            // Validate content-type is image
            $contentType = $response->header('Content-Type') ?? '';
            if (!str_starts_with($contentType, 'image/')) {
                logger()->warning('FAL image has invalid content-type', ['content_type' => $contentType]);
                return null;
            }

            // Validate image size (max 50MB)
            $imageSize = strlen($response->body());
            if ($imageSize > 50 * 1024 * 1024) {
                logger()->warning('FAL image exceeds size limit', ['size' => $imageSize]);
                return null;
            }

            // Store image
            $filename = 'fal-' . Str::uuid() . '.jpg';
            $path = \Illuminate\Support\Facades\Storage::disk('public')
                ->put('generated-images/' . $filename, $response->body());

            return $path ? '/generated-images/' . $filename : null;
        } catch (\Throwable $exception) {
            logger()->error('Failed to download FAL image', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);
            return null;
        }
    }
}
