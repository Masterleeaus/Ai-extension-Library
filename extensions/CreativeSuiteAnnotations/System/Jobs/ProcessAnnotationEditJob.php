<?php

declare(strict_types=1);

namespace App\Extensions\CreativeSuiteAnnotations\System\Jobs;

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use App\Extensions\CreativeSuiteAnnotations\System\Services\CreativeSuiteAnnotationsAIService;
use App\Models\UserOpenai;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessAnnotationEditJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public int $userOpenaiId,
        public string $prompt,
        public string $modelSlug,
        public string $imageDiskPath,
        public ?string $maskDiskPath = null,
        public int $creditCost = 0,
    ) {}

    public function handle(): void
    {
        $userOpenai = UserOpenai::query()->find($this->userOpenaiId);

        if (! $userOpenai) {
            $this->cleanupInputs();

            return;
        }

        $entity = EntityEnum::fromSlug($this->modelSlug) ?? EntityEnum::GPT_IMAGE_2;

        $image = $this->buildUploadedFile($this->imageDiskPath);
        $mask = $this->maskDiskPath ? $this->buildUploadedFile($this->maskDiskPath) : null;

        // Rethrow on failure so Laravel runs failed() exactly once — that's
        // the single site that marks the row failed and refunds credit.
        $result = CreativeSuiteAnnotationsAIService::dispatch(
            $this->prompt,
            $entity,
            $image,
            $mask,
        );

        if (($result['async'] ?? false) === true) {
            $payloadPatch = [
                'taskId' => $result['request_id'] ?? '',
            ];

            // Only the crop+composite finalize path needs the source image to
            // survive past job completion; every other async mode (e.g. the
            // multi-image-mask path) returns a fully composed image, so the
            // source can be cleaned up immediately to avoid orphaning it
            // in storage forever.
            $needsSource = isset($result['pending'])
                && (($result['pending']['mode'] ?? null) === 'crop_composite');

            if ($needsSource) {
                $payloadPatch['pending'] = array_merge($result['pending'], [
                    'image_disk_path' => $this->imageDiskPath,
                ]);
            }

            $userOpenai->update([
                'request_id' => $result['request_id'] ?? '',
                'payload'    => array_merge((array) $userOpenai->payload, $payloadPatch),
            ]);

            if ($needsSource) {
                $this->cleanupMaskOnly();
            } else {
                $this->cleanupInputs();
            }

            return;
        }

        $userOpenai->update([
            'output' => $result['output'] ?? '',
            'status' => 'COMPLETED',
        ]);

        $this->cleanupInputs();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Creative Suite Annotation edit job failed', [
            'user_openai_id' => $this->userOpenaiId,
            'model'          => $this->modelSlug,
            'error'          => $exception?->getMessage(),
        ]);

        $userOpenai = UserOpenai::query()->find($this->userOpenaiId);

        if ($userOpenai) {
            $userOpenai->update([
                'status'  => 'FAILED',
                'payload' => array_merge((array) $userOpenai->payload, [
                    'error_message' => $exception?->getMessage() ?? 'Unknown error',
                ]),
            ]);
        }

        if ($this->creditCost > 0 && $userOpenai?->user_id) {
            try {
                $entity = EntityEnum::tryFrom($this->modelSlug) ?? EntityEnum::GPT_IMAGE_2;
                Entity::driver($entity)
                    ->forUser($userOpenai->user_id)
                    ->increaseCredit((float) $this->creditCost);
            } catch (Throwable $e) {
                Log::error('Creative Suite Annotation refund failed', [
                    'user_openai_id' => $this->userOpenaiId,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        $this->cleanupInputs();
    }

    private function buildUploadedFile(string $relativePath): UploadedFile
    {
        $absolute = public_path('uploads/' . $relativePath);

        return new UploadedFile($absolute, basename($absolute), 'image/png', null, true);
    }

    private function cleanupInputs(): void
    {
        $paths = array_filter([$this->imageDiskPath, $this->maskDiskPath]);

        if ($paths === []) {
            return;
        }

        Storage::disk('public')->delete($paths);
    }

    private function cleanupMaskOnly(): void
    {
        if ($this->maskDiskPath) {
            Storage::disk('public')->delete($this->maskDiskPath);
        }
    }
}
