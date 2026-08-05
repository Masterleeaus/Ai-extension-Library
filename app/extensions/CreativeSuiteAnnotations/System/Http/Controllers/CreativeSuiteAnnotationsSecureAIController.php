<?php

declare(strict_types=1);

namespace App\Extensions\CreativeSuiteAnnotations\System\Http\Controllers;

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use App\Extensions\CreativeSuiteAnnotations\System\Services\CreativeSuiteAnnotationsAIService;
use App\Models\UserOpenai;
use App\Services\Ai\AIImageClient;
use App\Services\Security\RemoteImageFetcher;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Security-compatible controller overlay.
 *
 * The donor controller remains the compatibility anchor for edit/analyse.
 * This subclass overrides only asynchronous status/finalisation so existing
 * routes and request contracts stay unchanged while the risky boundary is
 * isolated behind owner scope and the shared remote-image fetcher.
 */
class CreativeSuiteAnnotationsSecureAIController extends CreativeSuiteAnnotationsAIController
{
    private const PENDING_STATUSES = ['CREATED', 'IN_PROGRESS', 'IN_QUEUE'];

    private const MAX_FINALIZE_ATTEMPTS = 5;

    public function status(string $task): JsonResponse
    {
        $userOpenai = UserOpenai::query()
            ->where('user_id', Auth::id())
            ->findOrFail((int) $task);

        if (in_array($userOpenai->status, self::PENDING_STATUSES, true)
            && ! empty($userOpenai->request_id)) {
            $entity = EntityEnum::tryFrom((string) data_get($userOpenai->payload, 'model'))
                ?? $this->getEntity();
            $resolution = $this->resolveAsyncResult($userOpenai->request_id, $entity);

            if ($resolution['state'] === 'failed') {
                $this->failAndRefund(
                    $userOpenai,
                    $resolution['error'] ?? 'Provider reported failure'
                );
                $userOpenai->refresh();
            } elseif ($resolution['state'] === 'ready' && isset($resolution['url'])) {
                $claimed = UserOpenai::query()
                    ->where('id', $userOpenai->getKey())
                    ->where('user_id', Auth::id())
                    ->whereIn('status', self::PENDING_STATUSES)
                    ->update(['status' => 'FINALIZING']);

                if ($claimed === 1) {
                    $download = $this->downloadToTemp($resolution['url']);

                    if ($download) {
                        $finalPath = $this->finalizeAsyncResult($userOpenai, $download);

                        if ($finalPath) {
                            $userOpenai->update([
                                'status' => 'COMPLETED',
                                'output' => $finalPath,
                            ]);
                        } else {
                            $this->handleFinalizeFailure($userOpenai);
                        }
                    } else {
                        $this->handleFinalizeFailure($userOpenai);
                    }

                    $userOpenai->refresh();
                }
            }
        }

        if ($userOpenai->output) {
            $userOpenai->output = $userOpenai->output_url;
        }

        return response()->json([
            'message' => __('creative-suite-annotations::creative-suite-annotations.generated'),
            'status'  => 'success',
            'data'    => $userOpenai,
        ]);
    }

    /**
     * @param array{path: string, mime: string, extension: string, size: int} $download
     */
    private function finalizeAsyncResult(UserOpenai $userOpenai, array $download): ?string
    {
        $editedTempPath = $download['path'] ?? null;
        $extension = $download['extension'] ?? null;

        if (! is_string($editedTempPath) || ! is_file($editedTempPath)
            || ! is_string($extension) || $extension === '') {
            if (is_string($editedTempPath)) {
                @unlink($editedTempPath);
            }

            return null;
        }

        $pending = data_get($userOpenai->payload, 'pending');

        try {
            if (is_array($pending) && ($pending['mode'] ?? null) === 'crop_composite') {
                $sourceDiskPath = $pending['image_disk_path'] ?? null;
                $cropOrigin = $pending['crop_origin'] ?? null;

                if (! is_string($sourceDiskPath) || $sourceDiskPath === ''
                    || ! is_array($cropOrigin)) {
                    return null;
                }

                $sourceAbsolute = public_path('uploads/' . $sourceDiskPath);

                if (! is_file($sourceAbsolute)) {
                    return null;
                }

                $finalPath = CreativeSuiteAnnotationsAIService::compositeBackToSource(
                    $sourceAbsolute,
                    $editedTempPath,
                    $cropOrigin,
                );

                Storage::disk('public')->delete($sourceDiskPath);

                return $finalPath;
            }

            $contents = file_get_contents($editedTempPath);
            if ($contents === false) {
                return null;
            }

            $relative = 'creative-suite-annotations/' . Str::uuid() . '.' . $extension;
            if (! Storage::disk('uploads')->put($relative, $contents)) {
                return null;
            }

            return '/uploads/' . $relative;
        } catch (Throwable) {
            return null;
        } finally {
            @unlink($editedTempPath);
        }
    }

    /**
     * @return array{path: string, mime: string, extension: string, size: int}|null
     */
    private function downloadToTemp(string $url): ?array
    {
        try {
            return app(RemoteImageFetcher::class)->fetch($url);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{state: string, url?: string, error?: string}
     */
    private function resolveAsyncResult(string $requestId, EntityEnum $entity): array
    {
        try {
            $result = AIImageClient::checkStatus($requestId, $entity->value);
        } catch (Exception) {
            return ['state' => 'pending'];
        }

        if (! is_array($result)) {
            return ['state' => 'pending'];
        }

        if (($result['status'] ?? null) === 'FAILED') {
            $error = (string) (data_get($result, 'error.message')
                ?? data_get($result, 'error')
                ?? 'Provider reported failure');

            return ['state' => 'failed', 'error' => $error];
        }

        $url = data_get($result, 'image.url')
            ?? data_get($result, 'images.0.url')
            ?? data_get($result, 'result.0');

        if (is_string($url) && $url !== '') {
            return ['state' => 'ready', 'url' => $url];
        }

        return ['state' => 'pending'];
    }

    private function failAndRefund(UserOpenai $userOpenai, string $error): void
    {
        $payload = (array) $userOpenai->payload;
        $creditCost = (int) ($payload['credit_cost'] ?? 0);

        $userOpenai->update([
            'status'  => 'FAILED',
            'payload' => array_merge($payload, ['error_message' => $error]),
        ]);

        $sourceDiskPath = data_get($payload, 'pending.image_disk_path');
        if (is_string($sourceDiskPath) && $sourceDiskPath !== '') {
            Storage::disk('public')->delete($sourceDiskPath);
        }

        if ($creditCost > 0 && $userOpenai->user_id) {
            try {
                $entity = EntityEnum::tryFrom((string) data_get($payload, 'model'))
                    ?? EntityEnum::GPT_IMAGE_2;
                Entity::driver($entity)
                    ->forUser($userOpenai->user_id)
                    ->increaseCredit((float) $creditCost);
            } catch (Exception) {
                // Refund is best-effort; the FAILED status is already stored.
            }
        }
    }

    private function handleFinalizeFailure(UserOpenai $userOpenai): void
    {
        $payload = (array) $userOpenai->payload;
        $attempts = (int) ($payload['finalize_attempts'] ?? 0) + 1;

        if ($attempts >= self::MAX_FINALIZE_ATTEMPTS) {
            $this->failAndRefund(
                $userOpenai,
                'Result finalize failed after maximum attempts.'
            );

            return;
        }

        $userOpenai->update([
            'status'  => 'IN_PROGRESS',
            'payload' => array_merge($payload, ['finalize_attempts' => $attempts]),
        ]);
    }

    private function getEntity(): EntityEnum
    {
        $model = (string) setting(
            'creative_suite_annotations_ai_model',
            config('creative-suite-annotations.default_model', 'gpt-image-2')
        );

        return EntityEnum::tryFrom($model) ?? EntityEnum::GPT_IMAGE_2;
    }
}
