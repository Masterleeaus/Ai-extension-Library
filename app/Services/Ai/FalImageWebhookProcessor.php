<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\SettingTwo;
use App\Models\UserOpenai;
use App\Services\Security\FalWebhookVerifier;
use App\Services\Security\RemoteImageFetcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FalImageWebhookProcessor
{
    public function __construct(
        private readonly FalWebhookVerifier $verifier,
        private readonly RemoteImageFetcher $remoteImageFetcher,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        try {
            $this->verifier->assertValid($request);
        } catch (Throwable) {
            return response()->json([
                'error' => 'Invalid FAL webhook authentication.',
            ], 403);
        }

        $signedRequestId = trim((string) $request->header('X-Fal-Webhook-Request-Id'));
        $bodyRequestId = trim((string) $request->input('request_id'));

        if ($bodyRequestId === '' || ! hash_equals($signedRequestId, $bodyRequestId)) {
            return response()->json([
                'error' => 'FAL webhook request ID mismatch.',
            ], 422);
        }

        $lock = Cache::lock('fal-webhook:' . hash('sha256', $signedRequestId), 30);

        if (! $lock->get()) {
            return response()->json([
                'message' => 'FAL webhook is already being processed.',
            ], 202);
        }

        try {
            $openai = UserOpenai::query()
                ->where('response', 'FL')
                ->where('request_id', $signedRequestId)
                ->first();

            if (! $openai) {
                return response()->json([
                    'error' => 'FAL task not found.',
                ], 404);
            }

            if (in_array((string) $openai->status, ['COMPLETED', 'FAILED'], true)) {
                return response()->json([
                    'message' => 'FAL task is already finalized.',
                ]);
            }

            $falStatus = strtoupper(trim((string) $request->input('status')));
            $payload = $request->input('payload');

            if ($falStatus === 'ERROR') {
                $openai->update([
                    'status' => 'FAILED',
                    'payload' => is_array($payload) ? $payload : $openai->payload,
                ]);

                return response()->json([
                    'message' => 'FAL task failure recorded.',
                ]);
            }

            if ($falStatus !== 'OK') {
                return response()->json([
                    'error' => 'Unsupported FAL webhook status.',
                ], 422);
            }

            if (! is_array($payload)) {
                return response()->json([
                    'error' => 'FAL webhook payload is invalid.',
                ], 422);
            }

            $images = data_get($payload, 'images');

            if (! is_array($images) || empty($images)) {
                return response()->json([
                    'error' => 'FAL webhook images are missing.',
                ], 422);
            }

            $firstImage = Arr::first($images);
            $imageUrl = is_string($firstImage)
                ? $firstImage
                : data_get($firstImage, 'url');

            if (! is_string($imageUrl) || trim($imageUrl) === '') {
                return response()->json([
                    'error' => 'FAL webhook image URL is invalid.',
                ], 422);
            }

            try {
                $output = $this->storeImage(trim($imageUrl));
            } catch (Throwable) {
                return response()->json([
                    'error' => 'FAL webhook image could not be stored.',
                ], 502);
            }

            $openai->update([
                'output' => $output,
                'payload' => $payload,
                'status' => 'COMPLETED',
            ]);

            return response()->json([
                'message' => 'FAL task completed.',
            ]);
        } finally {
            $lock->release();
        }
    }

    private function storeImage(string $url): string
    {
        $configuredStorage = SettingTwo::getCache()?->getAttribute('ai_image_storage');
        $disk = in_array($configuredStorage, ['r2', 's3'], true)
            ? $configuredStorage
            : 'public';

        $relativePath = $this->remoteImageFetcher->store(
            $url,
            $disk,
            'fal-ai'
        );

        if ($disk === 'r2' || $disk === 's3') {
            return Storage::disk($disk)->url($relativePath);
        }

        return '/uploads/' . $relativePath;
    }
}
