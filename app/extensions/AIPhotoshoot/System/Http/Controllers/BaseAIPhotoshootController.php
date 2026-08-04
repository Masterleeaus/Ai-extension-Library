<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Controllers;

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use App\Extensions\AIPhotoshoot\System\Enums\ImageStatusEnum;
use App\Extensions\AIPhotoshoot\System\Jobs\CheckAIPhotoshootGenerationJob;
use App\Extensions\AIPhotoshoot\System\Jobs\GenerateOpenAIImageJob;
use App\Extensions\AIPhotoshoot\System\Models\AIPhotoshootUserSetting;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootProviderFactory;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use App\Models\OpenAIGenerator;
use App\Models\Usage;
use App\Models\UserOpenai;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class BaseAIPhotoshootController extends Controller
{
    public function __construct(
        protected AIPhotoshootProviderFactory $providerFactory
    ) {}

    /**
     * Get the title for the generation record
     */
    abstract protected function getGenerationTitle(): string;

    /**
     * Get the slug suffix for the generation record
     */
    abstract protected function getSlugSuffix(): string;

    /**
     * Get the prompt for AI generation
     */
    abstract protected function getPrompt(): string;

    /**
     * Get image URLs from the request for AI generation
     *
     * @return array<int, string>
     */
    abstract protected function getImageUrls(): array;

    /**
     * Get the response key name for status endpoint
     */
    abstract protected function getResponseKey(): string;

    /**
     * The EntityEnum the current provider maps to — used for credit
     * calculation and record persistence.
     *
     * Defers to the admin-configurable registry so the same flow runs against
     * FAL or OpenAI based on configuration. Falls back to the legacy provider
     * factory's enum when the registry has nothing configured.
     */
    protected function editModel(): EntityEnum
    {
        return $this->getImageModel();
    }

    /**
     * Resolve the image model used for this generation request. Subclasses
     * may override to force a specific model.
     */
    protected function getImageModel(): EntityEnum
    {
        return AIPhotoshootImageModelRegistry::getDefaultModel();
    }

    /**
     * Get the number of images to generate (default 1)
     */
    protected function getNumImages(): int
    {
        return 1;
    }

    /**
     * Get credits per image (default 1)
     */
    protected function getCreditsPerImage(): int
    {
        return 1;
    }

    /**
     * Get the user's AI Photoshoot settings
     */
    protected function getUserSettings(): AIPhotoshootUserSetting
    {
        return AIPhotoshootUserSetting::getForUser(Auth::id());
    }

    /**
     * Resolve the FAL `aspect_ratio` + `resolution` to send to nano-banana-pro.
     *
     * Priority: per-record payload > user settings > registry defaults. Each
     * value is validated against the registry's allowed lists so a stale value
     * (e.g. an OpenAI-only ratio left over after the admin switched providers)
     * cannot reach FAL and get silently snapped to a different aspect.
     *
     * @return array{ratio: string, resolution: string}
     */
    protected function getFalRatioAndResolution(?UserOpenai $record = null): array
    {
        $payload = [];
        if ($record) {
            $payload = is_array($record->payload) ? $record->payload : (json_decode((string) $record->payload, true) ?: []);
        }

        $userSettings = $this->getUserSettings();

        $ratio = (string) ($payload['ratio'] ?? $userSettings->ratio ?? AIPhotoshootImageModelRegistry::FAL_RATIO_DEFAULT);
        if (! in_array($ratio, AIPhotoshootImageModelRegistry::FAL_RATIO_VALUES, true)) {
            $ratio = AIPhotoshootImageModelRegistry::FAL_RATIO_DEFAULT;
        }

        $resolution = (string) ($payload['resolution'] ?? $userSettings->resolution ?? AIPhotoshootImageModelRegistry::FAL_RESOLUTION_DEFAULT);
        if (! in_array($resolution, AIPhotoshootImageModelRegistry::FAL_RESOLUTION_VALUES, true)) {
            $resolution = AIPhotoshootImageModelRegistry::FAL_RESOLUTION_DEFAULT;
        }

        return ['ratio' => $ratio, 'resolution' => $resolution];
    }

    /**
     * Create database record for the generation
     */
    protected function createRecord(array $payloadData, int $imageIndex = 0): UserOpenai
    {
        $user = Auth::user();
        $model = $this->editModel();

        $record = UserOpenai::create([
            'team_id'   => $user?->team_id,
            'title'     => $this->getGenerationTitle() . ($imageIndex > 0 ? " #{$imageIndex}" : ''),
            'slug'      => Str::random(7) . Str::slug($user?->fullName()) . '-' . $this->getSlugSuffix() . ($imageIndex > 0 ? "-{$imageIndex}" : ''),
            'user_id'   => $user?->id,
            'openai_id' => OpenAIGenerator::where('slug', 'ai_image_generator')->first()?->id,
            'payload'   => $payloadData,
            'input'     => null,
            'response'  => 'APS',
            'output'    => null,
            'hash'      => str()->random(256),
            'credits'   => $this->getCreditsPerImage(),
            'words'     => 0,
            'storage'   => 'public',
            'status'    => ImageStatusEnum::pending->value,
            'model'     => $model,
            'engine'    => $model->engine()->value,
        ]);

        $record->is_ai_photo_studio = true;
        $record->save();

        return $record;
    }

    /**
     * Upload file securely and return the path
     */
    protected function uploadFile($file): string
    {
        $user = Auth::user();
        $folderPath = 'media/images/u-' . $user?->id;

        return processSecureFileUpload($file, $folderPath);
    }

    /**
     * Generate image with AI. Routes to FAL (async polling) or OpenAI
     * (async, dedicated job) based on the configured provider for the
     * active model.
     */
    protected function generateWithAI(UserOpenai $record): void
    {
        try {
            $model = $this->getImageModel();
            $provider = AIPhotoshootImageModelRegistry::getProviderFor($model);

            if ($provider === AIPhotoshootImageModelRegistry::PROVIDER_OPENAI) {
                $this->generateWithOpenAI($record, $model);

                return;
            }

            $this->generateWithFal($record);
        } catch (Exception $e) {
            Log::error($this->getGenerationTitle() . ' failed', [
                'record_id' => $record->id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            $record->update(['status' => ImageStatusEnum::failed->value]);
        }
    }

    /**
     * Async FAL flow: enqueue request via the provider factory and dispatch
     * the polling job to write the result back into the record.
     */
    protected function generateWithFal(UserOpenai $record): void
    {
        $provider = $this->providerFactory->make();

        $prompt = $this->getPrompt();
        $imageUrls = $this->getImageUrls();
        $numImages = $this->getNumImages();
        ['ratio' => $ratio, 'resolution' => $resolution] = $this->getFalRatioAndResolution($record);

        $requestId = $provider->generate($prompt, $imageUrls, $numImages, $ratio, $resolution);

        $existPayload = is_array($record->payload) ? $record->payload : [];
        $existPayload['uuid'] = $requestId;

        $record->update([
            'status'  => ImageStatusEnum::processing->value,
            'payload' => $existPayload,
        ]);

        CheckAIPhotoshootGenerationJob::dispatch($record->id)->delay(now()->addSeconds(5));
    }

    /**
     * Async OpenAI flow: enqueue GenerateOpenAIImageJob.
     *
     * gpt-image generations can exceed Cloudflare's 100s edge timeout, so we
     * never call OpenAIImageService::generate() during the HTTP request. The
     * job updates the record's status/output once it finishes, and the
     * frontend status endpoint picks up the change via polling.
     */
    protected function generateWithOpenAI(UserOpenai $record, EntityEnum $model): void
    {
        $prompt = $this->getPrompt();
        $imageUrls = $this->getImageUrls();
        $numImages = max(1, $this->getNumImages());

        $batchUuid = (string) Str::uuid();
        $payload = is_array($record->payload) ? $record->payload : [];
        $payload['uuid'] = $batchUuid;

        $record->update([
            'status'  => ImageStatusEnum::processing->value,
            'payload' => $payload,
        ]);

        $userSettings = $this->getUserSettings();
        $payloadArray = is_array($record->payload) ? $record->payload : (json_decode((string) $record->payload, true) ?: []);
        $resolution = (string) ($payloadArray['resolution'] ?? $userSettings->resolution);
        $ratio = (string) ($payloadArray['ratio'] ?? $userSettings->ratio);

        // The OpenAI dropdown only exposes ratios gpt-image can produce
        // exactly. If a stale value (e.g. 21:9 left over from a FAL run)
        // sneaks through, fall back to 'auto' rather than silently snapping
        // it to the closest landscape/portrait bucket.
        $openAIRatios = AIPhotoshootImageModelRegistry::getRatioOptionsForProvider(
            AIPhotoshootImageModelRegistry::PROVIDER_OPENAI
        );
        if (! array_key_exists($ratio, $openAIRatios)) {
            $ratio = 'auto';
        }

        $sizeOverride = AIPhotoshootImageModelRegistry::resolveOpenAISizeForUser($resolution, $ratio);

        GenerateOpenAIImageJob::dispatch(
            $record->id,
            'user_openai',
            $model->value,
            $prompt,
            $imageUrls,
            $numImages,
            $sizeOverride
        )->delay(now()->addSeconds(2));
    }

    /**
     * Download file from URL and save it locally
     */
    protected function downloadAndSaveFile(string $url, ?string $defaultExtension = null): ?string
    {
        try {
            $response = Http::timeout(120)->get($url);

            if ($response->successful()) {
                $extension = $defaultExtension ?? pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
                $fileName = Str::uuid() . '.' . $extension;
                $path = 'media/images/u-' . auth()->id() . '/' . $fileName;

                Storage::disk('public')->put($path, $response->body());

                return '/uploads/' . $path;
            }
        } catch (Exception $e) {
            Log::error('BaseAIPhotoshootController: Error downloading file', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Common status endpoint logic - checks database status (job updates the record)
     */
    public function status(string $id): JsonResponse
    {
        $record = UserOpenai::where('user_id', Auth::id())->findOrFail($id);

        $response = [
            'status' => $record->status,
        ];

        if ($record->status === ImageStatusEnum::completed->value) {
            $results = [[
                'id'         => $record->id,
                'image_url'  => $record->output,
                'thumbnail'  => ThumbImage($record->output),
                'created_at' => $record->created_at->toISOString(),
            ]];

            $payload = is_array($record->payload) ? $record->payload : (json_decode((string) $record->payload, true) ?: []);
            $uuid = $payload['uuid'] ?? null;

            if ($uuid) {
                $relatedRecords = UserOpenai::where('user_id', Auth::id())
                    ->where('id', '!=', $record->id)
                    ->where('status', ImageStatusEnum::completed->value)
                    ->where('created_at', '>=', $record->created_at->copy()->subMinutes(10))
                    ->where('created_at', '<=', $record->created_at->copy()->addMinutes(10))
                    ->whereJsonContains('payload->uuid', $uuid)
                    ->orderBy('id')
                    ->get();

                foreach ($relatedRecords as $relatedRecord) {
                    $results[] = [
                        'id'         => $relatedRecord->id,
                        'image_url'  => $relatedRecord->output,
                        'thumbnail'  => ThumbImage($relatedRecord->output),
                        'created_at' => $relatedRecord->created_at->toISOString(),
                    ];
                }
            }

            $response['results'] = $results;
        } elseif ($record->status === ImageStatusEnum::failed->value) {
            $response['message'] = __('Generation failed. Please try again.');
        }

        return response()->json($response);
    }

    /**
     * Common destroy endpoint logic
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $record = UserOpenai::where('user_id', Auth::id())->findOrFail($id);

            if ($record->output) {
                $relativePath = Str::after($record->output, '/uploads/');
                Storage::disk('public')->delete($relativePath);
            }

            $record->delete();

            return response()->json([
                'success' => true,
                'message' => __('Image deleted successfully'),
            ]);
        } catch (Exception $e) {
            Log::error(__('Failed to delete image'), [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Failed to delete image'),
            ], 500);
        }
    }

    /**
     * Get the demo limit feature name for rate limiting
     */
    protected function getDemoLimitFeature(): string
    {
        return 'default';
    }

    /**
     * Get the maximum demo attempts allowed per day
     */
    protected function getDemoMaxAttempts(): int
    {
        return 2;
    }

    /**
     * Common generate endpoint logic. Pass $skipDemoCheck=true when a caller
     * has already verified the demo-mode rate limit (e.g. multi-template
     * batch generation that runs this method N times for one user submission).
     */
    protected function processGeneration(string $lockKey, array $payloadData, bool $skipDemoCheck = false): JsonResponse
    {
        if (! $skipDemoCheck) {
            $demoLimitResponse = Helper::checkFashionStudioDemoLimit(
                $this->getDemoLimitFeature(),
                $this->getDemoMaxAttempts()
            );

            if ($demoLimitResponse !== null) {
                return $demoLimitResponse;
            }
        }

        $lock = Cache::lock($lockKey, 10);
        $acquired = false;

        try {
            if (! $lock->get()) {
                return response()->json([
                    'message' => __('Another editing in progress. Please try again later.'),
                ], 409);
            }
            $acquired = true;

            $numImages = $this->getNumImages();

            $driver = Entity::driver($this->editModel())
                ->inputImageCount($numImages)
                ->calculateCredit();

            if (! $driver->hasCreditBalanceForInput()) {
                return response()->json([
                    'success' => false,
                    'message' => __('You do not have enough credits. You need :credits credits to generate :count image(s).', [
                        'credits' => $driver->getCalculatedInputCredit(),
                        'count'   => $numImages,
                    ]),
                ], 402);
            }

            $record = $this->createRecord($payloadData);
            $this->generateWithAI($record);

            // If the synchronous dispatch in generateWithAI failed and already
            // marked the record FAILED, do not charge the user — nothing was
            // queued upstream.
            $record->refresh();
            if ($record->status === ImageStatusEnum::failed->value) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to start generation. Please try again.'),
                ], 500);
            }

            Usage::getSingle()->updateImageCounts($driver->calculate());
            $driver->decreaseCredit();

            return response()->json([
                'id'      => $record->id,
                'success' => true,
                'message' => __($this->getGenerationTitle() . ' started'),
            ]);
        } catch (Exception $e) {
            Log::error($this->getGenerationTitle() . ' failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Failed to start generation. Please try again.'),
            ], 500);
        } finally {
            if ($acquired) {
                $lock->release();
            }
        }
    }
}
