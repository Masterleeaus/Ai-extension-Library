<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Jobs;

use App\Extensions\AIPhotoshoot\System\Enums\ImageStatusEnum;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootFalService;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootProviderFactory;
use App\Models\UserOpenai;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CheckAIPhotoshootGenerationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 60;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 600;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public int $recordId,
        public string $type = 'image'
    ) {}

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [5, 5, 5, 10, 10, 10, 15, 15, 15, 20];
    }

    public function handle(AIPhotoshootProviderFactory $providerFactory): void
    {
        $record = UserOpenai::find($this->recordId);

        if (! $record) {
            Log::warning('CheckAIPhotoshootGenerationJob: Record not found', [
                'record_id' => $this->recordId,
            ]);

            return;
        }

        if (in_array($record->status, [ImageStatusEnum::completed->value, ImageStatusEnum::failed->value], true)) {
            return;
        }

        try {
            $payload = is_array($record->payload) ? $record->payload : (json_decode((string) $record->payload, true) ?: []);
            $uuid = $payload['uuid'] ?? null;

            if (! $uuid) {
                Log::error('CheckAIPhotoshootGenerationJob: No UUID found', [
                    'record_id' => $this->recordId,
                ]);

                $record->update(['status' => ImageStatusEnum::failed->value]);

                return;
            }

            if ($this->type === 'video') {
                $this->checkVideoGeneration($record, $uuid);
            } else {
                $this->checkImageGeneration($record, $uuid, $providerFactory);
            }
        } catch (RuntimeException $e) {
            Log::error('CheckAIPhotoshootGenerationJob: provider error — marking as failed', [
                'record_id' => $this->recordId,
                'error'     => $e->getMessage(),
            ]);

            $record->update(['status' => ImageStatusEnum::failed->value]);
            $this->delete();
        } catch (Exception $e) {
            Log::error('CheckAIPhotoshootGenerationJob: error checking status', [
                'record_id' => $this->recordId,
                'error'     => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $record->update(['status' => ImageStatusEnum::failed->value]);
            } else {
                throw $e;
            }
        }
    }

    protected function checkImageGeneration(UserOpenai $record, string $uuid, AIPhotoshootProviderFactory $providerFactory): void
    {
        $response = $providerFactory->make()->check($uuid);

        if ($response) {
            $images = data_get($response, 'images', []);

            if (! empty($images)) {
                $localPath = $this->resolveLocalPath($images[0], $record->user_id);

                if ($localPath) {
                    $record->update([
                        'status' => ImageStatusEnum::completed->value,
                        'output' => $localPath,
                    ]);

                    if (count($images) > 1) {
                        $this->createAdditionalImageRecords($record, array_slice($images, 1));
                    }

                    return;
                }
            }
        }

        $this->releaseOrFail($record);
    }

    protected function checkVideoGeneration(UserOpenai $record, string $uuid): void
    {
        $response = AIPhotoshootFalService::checkVideo($uuid);

        if ($response && isset($response['video_url'])) {
            $localPath = $this->downloadAndSaveVideo($response['video_url'], $record->user_id);

            if ($localPath) {
                $record->update([
                    'status' => ImageStatusEnum::completed->value,
                    'output' => $localPath,
                ]);

                return;
            }
        }

        $this->releaseOrFail($record);
    }

    protected function downloadAndSaveVideo(string $url, ?int $userId): ?string
    {
        try {
            $response = Http::timeout(120)->get($url);

            if ($response->successful()) {
                $fileName = Str::uuid() . '.mp4';
                $path = 'media/videos/u-' . $userId . '/' . $fileName;

                Storage::disk('public')->put($path, $response->body());

                return '/uploads/' . $path;
            }
        } catch (Exception $e) {
            Log::error('CheckAIPhotoshootGenerationJob: Error downloading video', [
                'url'       => $url,
                'error'     => $e->getMessage(),
                'record_id' => $this->recordId,
            ]);
        }

        return null;
    }

    /**
     * If the image is already served from /uploads (GPT Image 2 saves
     * locally), use it as-is. Otherwise download + persist.
     */
    protected function resolveLocalPath(string $url, ?int $userId): ?string
    {
        if (str_contains($url, '/uploads/')) {
            return '/' . ltrim(parse_url($url, PHP_URL_PATH) ?: $url, '/');
        }

        return $this->downloadAndSaveFile($url, $userId);
    }

    /**
     * Download file from URL and save it locally
     */
    protected function downloadAndSaveFile(string $url, ?int $userId, ?string $defaultExtension = null): ?string
    {
        try {
            $response = Http::timeout(120)->get($url);

            if ($response->successful()) {
                $extension = $defaultExtension ?? pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
                $fileName = Str::uuid() . '.' . $extension;
                $path = 'media/images/u-' . $userId . '/' . $fileName;

                Storage::disk('public')->put($path, $response->body());

                return '/uploads/' . $path;
            }
        } catch (Exception $e) {
            Log::error('CheckAIPhotoshootGenerationJob: Error downloading file', [
                'url'       => $url,
                'error'     => $e->getMessage(),
                'record_id' => $this->recordId,
            ]);
        }

        return null;
    }

    /**
     * Create separate UserOpenai records for additional images
     *
     * @param  array<int, string>  $additionalImages
     */
    protected function createAdditionalImageRecords(UserOpenai $record, array $additionalImages): void
    {
        foreach ($additionalImages as $index => $imageUrl) {
            $localPath = $this->resolveLocalPath($imageUrl, $record->user_id);

            if (! $localPath) {
                continue;
            }

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

    /**
     * Release job back to queue or mark as failed
     */
    protected function releaseOrFail(UserOpenai $record): void
    {
        if ($this->attempts() < $this->tries) {
            $this->release($this->getBackoffDelay());

            return;
        }

        $record->update(['status' => ImageStatusEnum::failed->value]);
        Log::warning('CheckAIPhotoshootGenerationJob: Generation timed out', [
            'record_id' => $this->recordId,
        ]);
    }

    /**
     * Get the backoff delay based on attempt number
     */
    protected function getBackoffDelay(): int
    {
        $attempt = $this->attempts();

        if ($attempt <= 10) {
            return 5;
        }

        if ($attempt <= 20) {
            return 10;
        }

        if ($attempt <= 40) {
            return 15;
        }

        return 20;
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        $record = UserOpenai::find($this->recordId);

        if ($record && $record->status !== ImageStatusEnum::completed->value) {
            $record->update(['status' => ImageStatusEnum::failed->value]);
        }

        Log::error('CheckAIPhotoshootGenerationJob: Job failed permanently', [
            'record_id' => $this->recordId,
            'error'     => $exception?->getMessage(),
        ]);
    }
}
