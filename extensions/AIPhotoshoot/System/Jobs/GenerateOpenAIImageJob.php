<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Jobs;

use App\Domains\Entity\Enums\EntityEnum;
use App\Extensions\AIPhotoshoot\System\Enums\ImageStatusEnum;
use App\Extensions\AIPhotoshoot\System\Models\AIPhotoshootBackground;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry;
use App\Models\UserOpenai;
use App\Services\Ai\Images\OpenAIImageService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Asynchronous OpenAI image generation for AI Photoshoot.
 *
 * Mirrors FashionStudio's GenerateOpenAIImageJob. Moves the long-running
 * synchronous OpenAIImageService::generate() call out of the HTTP request
 * to avoid Cloudflare's 100s edge timeout.
 *
 * One job dispatch per generation request. Loops $numImages times to
 * mirror the FAL multi-image behavior. For UserOpenai records with
 * numImages > 1, additional images create replicated UserOpenai rows.
 */
class GenerateOpenAIImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public bool $deleteWhenMissingModels = true;

    /**
     * Lock concurrent executions per record. Synchronous OpenAI image calls
     * routinely exceed the queue connection's `retry_after` (90s by default),
     * which causes the worker to re-release the job for retry while the
     * original worker is still mid-call. Without this lock, a second worker
     * starts a duplicate generation and Laravel raises
     * `MaxAttemptsExceededException` ("too many attempts") on it, prematurely
     * marking the record as failed even though the original call eventually
     * succeeds. The lock keeps a single worker on the job; the duplicate
     * pickup releases without running.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping((string) $this->recordId))
                ->releaseAfter(30)
                ->expireAfter($this->timeout + 60),
        ];
    }

    /**
     * Supported model types and their configurations.
     */
    private const MODEL_CONFIGS = [
        'user_openai' => [
            'class'  => UserOpenai::class,
            'output' => 'output',
        ],
        'background' => [
            'class'  => AIPhotoshootBackground::class,
            'output' => 'image_url',
        ],
    ];

    /**
     * @param  int  $recordId  Database id of the record to update on completion
     * @param  string  $modelType  One of MODEL_CONFIGS keys
     * @param  string  $modelValue  EntityEnum value (e.g. 'gpt-image-1')
     * @param  string  $prompt  Prompt to send to OpenAI
     * @param  array  $imageUrls  Optional reference images for image-edit calls
     * @param  int  $numImages  How many images to generate (loops the API call)
     * @param  ?string  $sizeOverride  Optional user-derived OpenAI size (e.g. '1024x1536').
     */
    public function __construct(
        public int $recordId,
        public string $modelType,
        public string $modelValue,
        public string $prompt,
        public array $imageUrls = [],
        public int $numImages = 1,
        public ?string $sizeOverride = null
    ) {}

    public function handle(): void
    {
        $record = $this->getRecord();

        if (! $record) {
            Log::warning('AIPhotoshoot GenerateOpenAIImageJob: Record not found', [
                'record_id'  => $this->recordId,
                'model_type' => $this->modelType,
            ]);

            return;
        }

        if (in_array($record->status, [ImageStatusEnum::completed->value, ImageStatusEnum::failed->value], true)) {
            return;
        }

        $model = EntityEnum::tryFrom($this->modelValue);

        if ($model === null) {
            Log::error('AIPhotoshoot GenerateOpenAIImageJob: Invalid model value', [
                'record_id'   => $this->recordId,
                'model_type'  => $this->modelType,
                'model_value' => $this->modelValue,
            ]);
            $record->update(['status' => ImageStatusEnum::failed->value]);

            return;
        }

        try {
            $service = app(OpenAIImageService::class);

            $options = [
                'model'        => $this->modelValue,
                'prompt'       => $this->prompt,
                'quality'      => AIPhotoshootImageModelRegistry::getOpenAIQuality(),
                'aspect_ratio' => $this->sizeOverride ?? AIPhotoshootImageModelRegistry::OPENAI_SIZE_DEFAULT,
            ];

            if (! empty($this->imageUrls)) {
                $options['image_reference'] = $this->imageUrls;
            }

            $numImages = max(1, $this->numImages);

            $firstPath = $this->generateSingleImage($service, $options, $record->user_id);

            if (! $firstPath) {
                $record->update(['status' => ImageStatusEnum::failed->value]);

                return;
            }

            $outputField = $this->getOutputField();

            $extraPaths = [];

            if ($this->modelType === 'user_openai' && $numImages > 1) {
                for ($i = 1; $i < $numImages; $i++) {
                    $extraPath = $this->generateSingleImage($service, $options, $record->user_id);

                    if ($extraPath) {
                        $extraPaths[] = $extraPath;
                    }
                }

                if (! empty($extraPaths)) {
                    $this->createAdditionalImageRecords($record, $extraPaths);
                }
            }

            $record->update([
                'status'     => ImageStatusEnum::completed->value,
                $outputField => $firstPath,
            ]);
        } catch (Throwable $e) {
            Log::error('AIPhotoshoot GenerateOpenAIImageJob: Generation failed', [
                'record_id'  => $this->recordId,
                'model_type' => $this->modelType,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            $record->update(['status' => ImageStatusEnum::failed->value]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        // A `MaxAttemptsExceededException` here typically means the queue's
        // `retry_after` fired while the original worker was still processing
        // (OpenAI synchronous calls can exceed the 90s default). The original
        // worker is the source of truth — let it complete and update the
        // record. Marking failed here would race with the in-flight success
        // and produce a misleading "Generation failed" toast for an image
        // that actually got generated.
        if ($exception instanceof MaxAttemptsExceededException) {
            Log::warning('AIPhotoshoot GenerateOpenAIImageJob: ignoring MaxAttemptsExceededException; original worker still owns the record', [
                'record_id'  => $this->recordId,
                'model_type' => $this->modelType,
            ]);

            return;
        }

        $record = $this->getRecord();

        if ($record && $record->status !== ImageStatusEnum::completed->value) {
            $record->update(['status' => ImageStatusEnum::failed->value]);
        }

        Log::error('AIPhotoshoot GenerateOpenAIImageJob: Job failed permanently', [
            'record_id'  => $this->recordId,
            'model_type' => $this->modelType,
            'error'      => $exception?->getMessage(),
        ]);
    }

    protected function getRecord(): ?Model
    {
        $config = self::MODEL_CONFIGS[$this->modelType] ?? null;

        if (! $config) {
            return null;
        }

        return $config['class']::find($this->recordId);
    }

    protected function getOutputField(): string
    {
        $config = self::MODEL_CONFIGS[$this->modelType] ?? null;

        return $config['output'] ?? 'output';
    }

    /**
     * Run a single OpenAIImageService call and persist the binary output.
     * Returns the public-uploads relative path or null on failure.
     */
    protected function generateSingleImage(OpenAIImageService $service, array $options, ?int $userId): ?string
    {
        try {
            $images = $service->generate($options);

            if (empty($images) || ! is_string($images[0])) {
                return null;
            }

            $fileName = Str::uuid()->toString() . '.png';
            $path = 'media/images/u-' . ($userId ?? 0) . '/' . $fileName;

            Storage::disk('public')->put($path, $images[0]);

            return '/uploads/' . $path;
        } catch (Exception $e) {
            Log::error('AIPhotoshoot GenerateOpenAIImageJob: OpenAI image generation failed', [
                'record_id'  => $this->recordId,
                'model_type' => $this->modelType,
                'error'      => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Replicate the original UserOpenai record once per additional image.
     */
    protected function createAdditionalImageRecords(Model $record, array $additionalPaths): void
    {
        foreach ($additionalPaths as $index => $localPath) {
            $imageNumber = $index + 2;

            $newRecord = $record->replicate();
            $newRecord->title = $record->title . " #{$imageNumber}";
            $newRecord->slug = str()->random(7) . '-' . $record->slug . "-{$imageNumber}";
            $newRecord->hash = str()->random(256);
            $newRecord->status = ImageStatusEnum::completed->value;
            $newRecord->output = $localPath;
            $newRecord->created_at = now();
            $newRecord->updated_at = now();
            $newRecord->save();
        }
    }
}
