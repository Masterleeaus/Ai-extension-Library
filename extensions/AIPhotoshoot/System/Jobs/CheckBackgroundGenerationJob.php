<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Jobs;

use App\Extensions\AIPhotoshoot\System\Enums\ImageStatusEnum;
use App\Extensions\AIPhotoshoot\System\Models\AIPhotoshootBackground;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootFalService;
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

class CheckBackgroundGenerationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 60;

    public int $timeout = 600;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public int $backgroundId
    ) {}

    public function backoff(): array
    {
        return [5, 5, 5, 10, 10, 10, 15, 15, 15, 20];
    }

    public function handle(): void
    {
        $background = AIPhotoshootBackground::find($this->backgroundId);

        if (! $background) {
            return;
        }

        if (in_array($background->status, [ImageStatusEnum::completed->value, ImageStatusEnum::failed->value], true)) {
            return;
        }

        try {
            $uuid = $background->generation_uuid;

            // OpenAI-generated backgrounds run synchronously via
            // GenerateOpenAIImageJob and don't have a polling UUID. If we ever
            // get dispatched against one, it's a no-op (already completed) or
            // a misconfiguration — fail safe rather than spam logs.
            if (! $uuid) {
                if ($background->status !== ImageStatusEnum::completed->value) {
                    $background->update(['status' => ImageStatusEnum::failed->value]);
                }

                return;
            }

            $service = new AIPhotoshootFalService;
            $response = $service->check($uuid);

            if ($response) {
                $images = data_get($response, 'images', []);

                if (! empty($images)) {
                    $localPath = $this->resolveLocalPath($images[0], $background->user_id);

                    if ($localPath) {
                        $background->update([
                            'status'    => ImageStatusEnum::completed->value,
                            'image_url' => $localPath,
                        ]);

                        return;
                    }
                }
            }

            $this->releaseOrFail($background);
        } catch (RuntimeException $e) {
            Log::error('CheckBackgroundGenerationJob: Fal AI error', [
                'background_id' => $this->backgroundId,
                'error'         => $e->getMessage(),
            ]);

            $background->update(['status' => ImageStatusEnum::failed->value]);
            $this->delete();
        } catch (Exception $e) {
            Log::error('CheckBackgroundGenerationJob: error checking status', [
                'background_id' => $this->backgroundId,
                'error'         => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $background->update(['status' => ImageStatusEnum::failed->value]);
            } else {
                throw $e;
            }
        }
    }

    protected function resolveLocalPath(string $url, ?int $userId): ?string
    {
        if (str_contains($url, '/uploads/')) {
            return '/' . ltrim(parse_url($url, PHP_URL_PATH) ?: $url, '/');
        }

        try {
            $response = Http::timeout(120)->get($url);

            if ($response->successful()) {
                $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
                $fileName = Str::uuid() . '.' . $extension;
                $path = 'media/images/u-' . $userId . '/' . $fileName;

                Storage::disk('public')->put($path, $response->body());

                return '/uploads/' . $path;
            }
        } catch (Exception $e) {
            Log::error('CheckBackgroundGenerationJob: Error downloading file', [
                'url'           => $url,
                'error'         => $e->getMessage(),
                'background_id' => $this->backgroundId,
            ]);
        }

        return null;
    }

    protected function releaseOrFail(AIPhotoshootBackground $background): void
    {
        if ($this->attempts() < $this->tries) {
            $this->release($this->getBackoffDelay());

            return;
        }

        $background->update(['status' => ImageStatusEnum::failed->value]);
    }

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

    public function failed(?Exception $exception): void
    {
        $background = AIPhotoshootBackground::find($this->backgroundId);

        if ($background && $background->status !== ImageStatusEnum::completed->value) {
            $background->update(['status' => ImageStatusEnum::failed->value]);
        }
    }
}
