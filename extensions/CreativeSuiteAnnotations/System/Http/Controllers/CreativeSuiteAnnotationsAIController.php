<?php

declare(strict_types=1);

namespace App\Extensions\CreativeSuiteAnnotations\System\Http\Controllers;

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use App\Extensions\CreativeSuiteAnnotations\System\Http\Requests\CreativeSuiteAnnotationsAnalyzeRequest;
use App\Extensions\CreativeSuiteAnnotations\System\Http\Requests\CreativeSuiteAnnotationsEditRequest;
use App\Extensions\CreativeSuiteAnnotations\System\Jobs\ProcessAnnotationEditJob;
use App\Extensions\CreativeSuiteAnnotations\System\Services\CreativeSuiteAnnotationsAIService;
use App\Extensions\CreativeSuiteAnnotations\System\Services\CreativeSuiteAnnotationsVisionService;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use App\Models\OpenAIGenerator;
use App\Models\Usage;
use App\Models\UserOpenai;
use App\Services\Ai\AIImageClient;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CreativeSuiteAnnotationsAIController extends Controller
{
    private const PENDING_STATUSES = ['CREATED', 'IN_PROGRESS', 'IN_QUEUE'];

    /**
     * Vision-capable models the admin can pick for the Replace Text tool.
     * Curated rather than auto-derived because EntityEnum has no
     * first-class vision capability flag. Update when new models land.
     */
    public const VISION_CAPABLE_MODELS = [
        // OpenAI — GPT-5.x is the latest family
        EntityEnum::GPT_5_4_NANO,
        EntityEnum::GPT_5_4_MINI,
        EntityEnum::GPT_5_4,
        EntityEnum::GPT_5_5,
        EntityEnum::GPT_5_2_PRO,
        EntityEnum::GPT_5_PRO,
        EntityEnum::GPT_5,

        // Anthropic — Claude 4.x supports vision
        EntityEnum::CLAUDE_OPUS_4_8,
        EntityEnum::CLAUDE_OPUS_4_7,
        EntityEnum::CLAUDE_OPUS_4_6,
        EntityEnum::CLAUDE_SONNET_4_6,
        EntityEnum::CLAUDE_SONNET_4_5,

        // Google — current-generation Gemini only (3.x + 2.5)
        EntityEnum::GEMINI_3_1_PRO_PREVIEW,
        EntityEnum::GEMINI_3_PRO_PREVIEW,
        EntityEnum::GEMINI_3_FLASH,
        EntityEnum::GEMINI_2_5_PRO,
        EntityEnum::GEMINI_2_5_FLASH_PREVIEW_05_20,

        // xAI — Grok 4 supports image input
        EntityEnum::GROK_4_FAST,
        EntityEnum::GROK_4,
    ];

    public function edit(CreativeSuiteAnnotationsEditRequest $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return $this->errorResponse(__('creative-suite-annotations::creative-suite-annotations.demo_disabled'));
        }

        $lockKey = 'csa-' . Auth::id();
        $lock = Cache::lock($lockKey, 10);

        if (! $lock->get()) {
            return response()->json([
                'message' => __('creative-suite-annotations::creative-suite-annotations.in_progress'),
            ], 409);
        }

        $entity = $this->getEntity();
        $driver = Entity::driver($entity)->inputImageCount(1)->calculateCredit();
        $creditCost = (int) $driver->calculate();

        try {
            $driver->redirectIfNoCreditBalance();
        } catch (Exception) {
            $lock->release();

            return $this->errorResponse(__('creative-suite-annotations::creative-suite-annotations.no_credits'));
        }

        try {
            $imageFile = $request->file('image_reference');
            $maskFile = $request->file('mask');

            if (! isFileSecure($imageFile) || ! isFileSecure($maskFile)) {
                $lock->release();
                $driver->increaseCredit($creditCost);

                return $this->errorResponse(__('File is not allowed for security reasons.'));
            }

            $imageDiskPath = $this->stashUpload($imageFile);
            $maskDiskPath = $this->stashUpload($maskFile);

            $prompt = $this->resolvePrompt($request);

            $data = $this->buildUserOpenaiPayload($request, $entity, $prompt, $creditCost);
            $userOpenai = UserOpenai::query()->create($data);

            Usage::getSingle()->updateImageCounts($creditCost);
            $driver->decreaseCredit();

            // On a real queue worker (database/redis/sqs) push the job onto
            // the queue and let the worker handle the long-running AI call.
            // On `sync` (no worker installed) fall back to dispatching after
            // the HTTP response is sent so the user doesn't see a 504 while
            // gpt-image-2 streams for 1–3 minutes.
            if (config('queue.default') === 'sync') {
                ProcessAnnotationEditJob::dispatchAfterResponse(
                    $userOpenai->getKey(),
                    $prompt,
                    $entity->value,
                    $imageDiskPath,
                    $maskDiskPath,
                    $creditCost,
                );
            } else {
                dispatch(new ProcessAnnotationEditJob(
                    $userOpenai->getKey(),
                    $prompt,
                    $entity->value,
                    $imageDiskPath,
                    $maskDiskPath,
                    $creditCost,
                ))->delay(1);
            }

            $lock->release();

            return response()->json([
                'message' => __('creative-suite-annotations::creative-suite-annotations.generated'),
                'status'  => 'success',
                'data'    => array_merge($data, [
                    'id'      => $userOpenai->getKey(),
                    'output'  => $userOpenai->output_url,
                    'lockKey' => $lockKey,
                ]),
            ]);
        } catch (Exception $e) {
            $lock->release();
            $driver->increaseCredit($creditCost);

            return $this->errorResponse($e->getMessage());
        }
    }

    public function analyze(CreativeSuiteAnnotationsAnalyzeRequest $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return $this->errorResponse(__('creative-suite-annotations::creative-suite-annotations.demo_disabled'));
        }

        $entity = $this->resolveVisionEntity();
        $driver = Entity::driver($entity);

        try {
            $driver->redirectIfNoCreditBalance();
        } catch (Exception) {
            return $this->errorResponse(__('creative-suite-annotations::creative-suite-annotations.no_credits'));
        }

        $imageFile = $request->file('image');

        if (! isFileSecure($imageFile)) {
            return $this->errorResponse(__('File is not allowed for security reasons.'));
        }

        try {
            $result = CreativeSuiteAnnotationsVisionService::analyze(
                $imageFile,
                $entity,
            );

            // Vision models are token-priced — bill against the response
            // we just received so the admin's per-token rate applies.
            $jsonOutput = json_encode($result, JSON_THROW_ON_ERROR);
            $driver->input($jsonOutput)->calculateCredit()->decreaseCredit();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return response()->json([
            'status'  => 'success',
            'message' => __('creative-suite-annotations::creative-suite-annotations.generated'),
            'data'    => $result,
        ]);
    }

    private function stashUpload(UploadedFile $file): string
    {
        return $file->store('creative-suite-annotations/inputs', 'public');
    }

    private const MAX_FINALIZE_ATTEMPTS = 5;

    public function status(string $task): JsonResponse
    {
        $userOpenai = UserOpenai::query()
            ->where('user_id', Auth::id())
            ->findOrFail((int) $task);

        if (in_array($userOpenai->status, self::PENDING_STATUSES, true) && ! empty($userOpenai->request_id)) {
            $entity = EntityEnum::tryFrom((string) data_get($userOpenai->payload, 'model')) ?? $this->getEntity();
            $resolution = $this->resolveAsyncResult($userOpenai->request_id, $entity);

            if ($resolution['state'] === 'failed') {
                $this->failAndRefund($userOpenai, $resolution['error'] ?? 'Provider reported failure');
                $userOpenai->refresh();
            } elseif ($resolution['state'] === 'ready' && isset($resolution['url'])) {
                // Atomically claim this pending row so two concurrent polls
                // can't both download + finalize the same async result.
                $claimed = UserOpenai::query()
                    ->where('id', $userOpenai->getKey())
                    ->whereIn('status', self::PENDING_STATUSES)
                    ->update(['status' => 'FINALIZING']);

                if ($claimed === 1) {
                    $editedPath = $this->downloadToTemp($resolution['url']);

                    if ($editedPath) {
                        $finalPath = $this->finalizeAsyncResult($userOpenai, $editedPath);

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
     * Stitch the async provider's edited crop back into the original full
     * image when the pending payload tells us this was the crop+composite
     * fallback path. Otherwise just promote the temp file to a permanent
     * uploads path.
     */
    private function finalizeAsyncResult(UserOpenai $userOpenai, string $editedTempPath): ?string
    {
        $pending = data_get($userOpenai->payload, 'pending');

        try {
            if (is_array($pending) && ($pending['mode'] ?? null) === 'crop_composite') {
                $sourceDiskPath = $pending['image_disk_path'] ?? null;
                $cropOrigin = $pending['crop_origin'] ?? null;

                if (! $sourceDiskPath || ! is_array($cropOrigin)) {
                    return null;
                }

                $sourceAbsolute = public_path('uploads/' . $sourceDiskPath);

                if (! file_exists($sourceAbsolute)) {
                    return null;
                }

                $finalPath = CreativeSuiteAnnotationsAIService::compositeBackToSource(
                    $sourceAbsolute,
                    $editedTempPath,
                    $cropOrigin,
                );

                Storage::disk('public')->delete($sourceDiskPath);
                @unlink($editedTempPath);

                return $finalPath;
            }

            // Plain async result — promote temp file to a stored upload.
            $fileName = Str::uuid() . '.png';
            $relative = 'creative-suite-annotations/' . $fileName;
            Storage::disk('uploads')->put($relative, file_get_contents($editedTempPath));
            @unlink($editedTempPath);

            return '/uploads/' . $relative;
        } catch (Exception) {
            @unlink($editedTempPath);

            return null;
        }
    }

    private function downloadToTemp(string $url): ?string
    {
        $response = Http::get($url);

        if (! $response->successful()) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'csa-edit-') . '.png';
        file_put_contents($tempPath, $response->body());

        return $tempPath;
    }

    /**
     * Resolve an async generation. Returns one of:
     *   ['state' => 'pending']
     *   ['state' => 'ready',  'url' => string]
     *   ['state' => 'failed', 'error' => string]
     *
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

    /**
     * Mark a pending async row as FAILED, refund credits, and clean up any
     * source image we kept around for the crop+composite finalize path.
     */
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
                // Refund best-effort; the FAILED status is already persisted.
            }
        }
    }

    /**
     * A poll that claimed the row and then couldn't finalize. Track attempts
     * in payload; after MAX_FINALIZE_ATTEMPTS fall through to the FAILED+refund
     * path so the user isn't billed indefinitely for a permanently broken
     * upstream URL.
     */
    private function handleFinalizeFailure(UserOpenai $userOpenai): void
    {
        $payload = (array) $userOpenai->payload;
        $attempts = (int) ($payload['finalize_attempts'] ?? 0) + 1;

        if ($attempts >= self::MAX_FINALIZE_ATTEMPTS) {
            $this->failAndRefund($userOpenai, 'Result finalize failed after maximum attempts.');

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

        // tryFrom (not fromSlug) — fromSlug calls EntityRemover::removeEntity()
        // as a side effect on unknown values and then throws. We want a clean
        // fallback when the admin-configured slug isn't in the enum.
        return EntityEnum::tryFrom($model) ?? EntityEnum::GPT_IMAGE_2;
    }

    /**
     * Resolve the vision-capable entity used by the Replace Text tool's
     * Analyze step. Cascade: admin setting → preferred GPT-5.4 nano/mini/full
     * → system-wide default OpenAI model. Each step uses tryFrom so missing
     * enum cases fall through cleanly.
     */
    private function resolveVisionEntity(): EntityEnum
    {
        $configured = (string) setting('creative_suite_annotations_vision_model');
        if ($configured !== '' && ($entity = EntityEnum::tryFrom($configured)) !== null) {
            return $entity;
        }

        // Lead with OpenAI since most installs already have an OpenAI key
        // configured — defaulting to Gemini would silently fail for those
        // users. Within OpenAI: gpt-5.4-mini is the best price/accuracy
        // balance for OCR-style grounding. Gemini Flash slots in after as
        // a sensible alternative for installs that have a Gemini key but
        // no OpenAI key.
        foreach ([
            'gpt-5.4-mini',
            'gpt-5.4',
            'gpt-5.4-nano',
            'gemini-3-flash-preview',
            'gemini-2.5-flash-preview-05-20',
        ] as $candidate) {
            if (($entity = EntityEnum::tryFrom($candidate)) !== null) {
                return $entity;
            }
        }

        $fallback = EntityEnum::tryFrom((string) setting('openai_default_model'));
        if ($fallback !== null) {
            return $fallback;
        }

        throw new RuntimeException(__('No vision-capable model is configured.'));
    }

    /**
     * Compose the final prompt sent to the AI provider. For comments we
     * wrap raw per-pin prompts (`pin_prompts`) with marker scaffolding
     * server-side so the prompt template stays out of the client bundle.
     */
    private function resolvePrompt(CreativeSuiteAnnotationsEditRequest $request): string
    {
        $tool = (string) $request->validated('tool');

        if ($tool === 'comment') {
            $raw = $request->validated('pin_prompts');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;

            return CreativeSuiteAnnotationsAIService::buildCommentPrompt(
                is_array($decoded) ? $decoded : []
            );
        }

        if ($tool === 'replace-text') {
            $raw = $request->validated('text_edits');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;

            return CreativeSuiteAnnotationsAIService::buildReplaceTextPrompt(
                is_array($decoded) ? $decoded : []
            );
        }

        return CreativeSuiteAnnotationsAIService::buildRegionPrompt(
            $tool,
            (string) $request->validated('prompt'),
        );
    }

    private function buildUserOpenaiPayload(
        CreativeSuiteAnnotationsEditRequest $request,
        EntityEnum $entity,
        string $prompt,
        int $creditCost = 0,
    ): array {
        $openai = OpenAIGenerator::query()->where('slug', 'ai_image_generator')->firstOrFail();

        return [
            'request_id' => '',
            'team_id'    => Auth::user()?->teamId(),
            'title'      => Str::limit($prompt, 20) ?: __('Creative Suite Annotation'),
            'slug'       => Str::random(7) . Str::slug(Auth::user()?->fullName()) . '-annotation',
            'user_id'    => Auth::id(),
            'openai_id'  => $openai->getKey(),
            'input'      => $prompt,
            'response'   => 'CD',
            'output'     => '',
            'hash'       => Str::random(256),
            'credits'    => 1,
            'words'      => 0,
            'storage'    => 'public',
            'payload'    => [
                'taskId'      => '',
                'tool'        => $request->validated('tool'),
                'model'       => $entity->value,
                // Persisted so status() can refund on async-only failures
                // (the original Job::failed() refund path doesn't fire when
                // queueing succeeded but the upstream provider later failed).
                'credit_cost' => $creditCost,
            ],
            'status' => 'IN_PROGRESS',
            'model'  => $entity->value,
            'engine' => $entity->engine()?->value,
        ];
    }

    private function errorResponse(string $message): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'type'    => 'error',
            'message' => $message,
        ]);
    }
}
