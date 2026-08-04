<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Controllers;

use App\Domains\Entity\Facades\Entity;
use App\Extensions\AIPhotoshoot\System\Enums\ImageStatusEnum;
use App\Extensions\AIPhotoshoot\System\Http\Controllers\Concerns\DispatchesAssetCreation;
use App\Extensions\AIPhotoshoot\System\Models\AIPhotoshootBackground;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry;
use App\Http\Controllers\Controller;
use App\Models\Usage;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackgroundController extends Controller
{
    use DispatchesAssetCreation;

    /**
     * Load user's uploaded backgrounds
     */
    public function load(Request $request): JsonResponse
    {
        $backgrounds = AIPhotoshootBackground::where('user_id', Auth::id())
            ->where('status', '!=', ImageStatusEnum::failed->value)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($item) => [
                'id'         => $item->id,
                'name'       => $item->name,
                'category'   => $item->category,
                'image_url'  => $item->image_url,
                'thumbnail'  => ThumbImage($item->image_url),
                'exist_type' => $item->exist_type,
                'status'     => $item->status ?? 'completed',
                'created_at' => $item->created_at,
            ]);

        return response()->json([
            'success'     => true,
            'backgrounds' => $backgrounds,
        ]);
    }

    /**
     * Upload a new background
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'background_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:' . AIPhotoshootImageModelRegistry::getMaxUploadSizeKb(),
            'name'             => 'nullable|string|max:255',
            'category'         => 'nullable|string|max:100',
        ]);

        try {
            $userId = Auth::id();
            $file = $request->file('background_image');
            $url = processSecureFileUpload($file, 'media/images/u-' . $userId);

            $background = AIPhotoshootBackground::create([
                'user_id'    => $userId,
                'name'       => $request->input('name') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'category'   => $request->input('category') ?: 'other',
                'image_url'  => $url,
                'exist_type' => 'uploaded',
                'status'     => ImageStatusEnum::completed->value,
            ]);

            return response()->json([
                'success'    => true,
                'message'    => __('Background uploaded successfully'),
                'background' => [
                    'id'         => $background->id,
                    'name'       => $background->name,
                    'category'   => $background->category,
                    'image_url'  => $background->image_url,
                    'thumbnail'  => ThumbImage($background->image_url),
                    'exist_type' => $background->exist_type,
                    'status'     => $background->status,
                    'created_at' => $background->created_at,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('AIPhotoshoot background upload failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => __('Failed to upload background: ' . $e->getMessage()),
            ], 500);
        }
    }

    /**
     * Create AI-generated background
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'background_description' => 'required|string|min:10|max:1000',
        ]);

        $lockKey = 'aps-background-create-' . Auth::id() . '-' . now()->timestamp;
        $lock = Cache::lock($lockKey, 10);
        $acquired = false;

        try {
            if (! $lock->get()) {
                return response()->json([
                    'message' => __('Another generation in progress. Please try again later.'),
                ], 409);
            }
            $acquired = true;

            $resolvedModel = $this->resolveAssetCreationModel();
            $driver = Entity::driver($resolvedModel)->inputImageCount(1)->calculateCredit();

            try {
                $driver->redirectIfNoCreditBalance();
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => __('You do not have enough credits to create a background.'),
                ], 402);
            }

            $userId = Auth::id();
            $description = (string) $request->input('background_description');

            $prompt = "Professional photography background scene: {$description}. High quality, detailed environment, professional lighting, 4K resolution.";

            $background = AIPhotoshootBackground::create([
                'user_id'     => $userId,
                'name'        => __('AI Generated Background'),
                'category'    => 'other',
                'description' => $description,
                'image_url'   => '/themes/default/assets/img/loading.svg',
                'exist_type'  => 'created',
                'status'      => ImageStatusEnum::pending->value,
            ]);

            // dispatchAssetGeneration marks the record FAILED and re-throws
            // on dispatch failure, so credit deduction below is skipped via
            // the outer catch — the user is not charged for a request that
            // never reached the provider.
            $this->dispatchAssetGeneration($background, $prompt, 'background', $resolvedModel);

            Usage::getSingle()->updateImageCounts($driver->calculate());
            $driver->decreaseCredit();

            return response()->json([
                'success'    => true,
                'message'    => __('Background generation started'),
                'background' => [
                    'id'         => $background->id,
                    'name'       => $background->name,
                    'category'   => $background->category,
                    'image_url'  => $background->image_url,
                    'thumbnail'  => $background->image_url,
                    'exist_type' => $background->exist_type,
                    'status'     => $background->status,
                    'created_at' => $background->created_at,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('AIPhotoshoot background creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Failed to create background. Please try again.'),
            ], 500);
        } finally {
            if ($acquired) {
                $lock->release();
            }
        }
    }

    public function checkStatus(string $id): JsonResponse
    {
        $background = AIPhotoshootBackground::where('user_id', Auth::id())->findOrFail($id);

        $response = [
            'success' => true,
            'status'  => strtolower((string) $background->status),
        ];

        if ($background->status === ImageStatusEnum::completed->value) {
            $response['background'] = [
                'id'         => $background->id,
                'name'       => $background->name,
                'category'   => $background->category,
                'image_url'  => $background->image_url,
                'thumbnail'  => ThumbImage($background->image_url),
                'exist_type' => $background->exist_type,
                'status'     => $background->status,
                'created_at' => $background->created_at,
            ];
        } elseif ($background->status === ImageStatusEnum::failed->value) {
            $response['success'] = false;
            $response['message'] = __('Background generation failed. Please try again.');
        }

        return response()->json($response);
    }

    /**
     * Delete a background
     */
    public function delete(string $backgroundId): JsonResponse
    {
        try {
            $background = AIPhotoshootBackground::where('id', $backgroundId)
                ->where('user_id', Auth::id())
                ->first();

            if (! $background) {
                return response()->json([
                    'success' => false,
                    'message' => __('Background not found or unauthorized'),
                ], 404);
            }

            if ($background->image_url && str_contains($background->image_url, '/uploads/')) {
                $relativePath = Str::after($background->image_url, '/uploads/');
                Storage::disk('public')->delete($relativePath);
            }

            $background->delete();

            return response()->json([
                'success' => true,
                'message' => __('Background deleted successfully'),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete background: ' . $e->getMessage()),
            ], 500);
        }
    }
}
