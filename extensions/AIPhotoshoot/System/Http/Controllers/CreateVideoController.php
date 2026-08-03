<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Controllers;

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use App\Extensions\AIPhotoshoot\System\Enums\ImageStatusEnum;
use App\Extensions\AIPhotoshoot\System\Jobs\CheckAIPhotoshootGenerationJob;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootFalService;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry;
use App\Helpers\Classes\Helper;
use App\Models\OpenAIGenerator;
use App\Models\Usage;
use App\Models\UserOpenai;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CreateVideoController extends BaseAIPhotoshootController
{
    private array $uploadedPaths = [];

    private string $userPrompt = '';

    public function createVideo(?string $image_id = null): View
    {
        return view('ai-photoshoot::create-video', [
            'image_id' => $image_id,
        ]);
    }

    public function generateVideo(Request $request): JsonResponse
    {
        $request->validate([
            'image'  => 'required|image|max:' . AIPhotoshootImageModelRegistry::getMaxUploadSizeKb(),
            'prompt' => 'required|string|max:1000',
        ]);

        $lockKey = $request->lock_key ?? 'aps-video-request-' . now()->timestamp . '-' . auth()->id();

        $imagePath = $this->uploadFile($request->file('image'));

        $this->uploadedPaths = ['image' => url($imagePath)];
        $this->userPrompt = $request->get('prompt', '');

        return $this->processVideoGeneration($lockKey, [
            'image_path' => url($imagePath),
            'type'       => 'video',
        ]);
    }

    public function videoStatus(string $id): JsonResponse
    {
        $record = UserOpenai::where('user_id', Auth::id())->findOrFail($id);

        $response = [
            'status' => $record->status,
        ];

        if ($record->status === ImageStatusEnum::completed->value) {
            $response['results'] = [[
                'id'        => $record->id,
                'video_url' => $record->output,
                'type'      => 'video',
            ]];
        } elseif ($record->status === ImageStatusEnum::failed->value) {
            $response['message'] = __('Video generation failed. Please try again.');
        }

        return response()->json($response);
    }

    protected function processVideoGeneration(string $lockKey, array $payloadData): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'message' => __('This feature is disabled in demo mode.'),
            ], 422);
        }

        try {
            if (! Cache::lock($lockKey, 10)->get()) {
                return response()->json([
                    'message' => __('Another video generation in progress. Please try again later.'),
                ], 409);
            }

            $videoModel = $this->getVideoModelEntity();

            $driver = Entity::driver($videoModel)
                ->inputVideoCount(1)
                ->calculateCredit();

            try {
                $driver->redirectIfNoCreditBalance();
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => __('You do not have enough credits to generate a video.'),
                ], 402);
            }

            $record = $this->createVideoRecord($payloadData);
            $this->generateVideoWithAI($record);

            Usage::getSingle()->updateImageCounts($driver->calculate());
            $driver->decreaseCredit();
            Cache::lock($lockKey)->release();

            return response()->json([
                'id'      => $record->id,
                'success' => true,
                'message' => __('Video generation started'),
            ]);

        } catch (Exception $e) {
            Log::error('AIPhotoshoot video generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Failed to start video generation: ' . $e->getMessage()),
            ], 500);

        } finally {
            Cache::lock($lockKey)->forceRelease();
        }
    }

    protected function getVideoModelEntity(): EntityEnum
    {
        $modelSetting = setting('ai-photoshoot-video-default-model', EntityEnum::VEO_3_1_IMAGE_TO_VIDEO->value);

        return EntityEnum::fromSlug($modelSetting) ?? EntityEnum::VEO_3_1_IMAGE_TO_VIDEO;
    }

    protected function createVideoRecord(array $payloadData): UserOpenai
    {
        $user = Auth::user();
        $videoModel = $this->getVideoModelEntity();

        $record = UserOpenai::create([
            'team_id'   => $user?->team_id,
            'title'     => $this->getGenerationTitle(),
            'slug'      => Str::random(7) . Str::slug($user?->fullName()) . '-' . $this->getSlugSuffix(),
            'user_id'   => $user?->id,
            'openai_id' => OpenAIGenerator::where('slug', 'ai_video')->first()?->id
                ?? OpenAIGenerator::where('slug', 'ai_image_generator')->first()?->id,
            'payload'   => $payloadData,
            'input'     => null,
            'response'  => 'APS-VIDEO',
            'output'    => null,
            'hash'      => str()->random(256),
            'credits'   => $this->getCreditsPerImage(),
            'words'     => 0,
            'storage'   => 'public',
            'status'    => ImageStatusEnum::pending->value,
            'model'     => $videoModel->value,
            'engine'    => $videoModel->engine()->value,
        ]);

        $record->is_ai_photo_studio = true;
        $record->save();

        return $record;
    }

    protected function generateVideoWithAI(UserOpenai $record): void
    {
        try {
            $prompt = $this->getPrompt();
            $imageUrl = $this->uploadedPaths['image'];

            $requestId = AIPhotoshootFalService::generateVideo($prompt, $imageUrl);

            $existPayload = is_array($record->payload) ? $record->payload : (json_decode((string) $record->payload, true) ?: []);
            $existPayload['uuid'] = $requestId;
            $existPayload['video_model'] = setting('ai-photoshoot-video-default-model', EntityEnum::VEO_3_1_IMAGE_TO_VIDEO->value);

            $record->update([
                'status'  => ImageStatusEnum::processing->value,
                'payload' => $existPayload,
            ]);

            CheckAIPhotoshootGenerationJob::dispatch($record->id, 'video')->delay(now()->addSeconds(10));
        } catch (Exception $e) {
            Log::error('AIPhotoshoot video generation failed', [
                'record_id' => $record->id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            $record->update(['status' => ImageStatusEnum::failed->value]);
        }
    }

    protected function getGenerationTitle(): string
    {
        return __('Create Video Generation');
    }

    protected function getSlugSuffix(): string
    {
        return 'create-video';
    }

    protected function getPrompt(): string
    {
        return $this->userPrompt;
    }

    protected function getImageUrls(): array
    {
        return [$this->uploadedPaths['image'] ?? ''];
    }

    protected function getResponseKey(): string
    {
        return 'create_video';
    }
}
