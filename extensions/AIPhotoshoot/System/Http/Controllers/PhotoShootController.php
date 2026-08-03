<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Controllers;

use App\Extensions\AIPhotoshoot\System\Enums\ImageStatusEnum;
use App\Helpers\Classes\Helper;
use App\Models\UserOpenai;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PhotoShootController extends BaseAIPhotoshootController
{
    public function myPhotoshoots(): View
    {
        return view('ai-photoshoot::photoshoots.my');
    }

    public function loadImages(Request $request): JsonResponse
    {
        $perPage = 100;
        $page = (int) $request->get('page', 1);
        $userId = Auth::id();
        $type = (string) $request->get('type', 'all');

        $query = UserOpenai::where('user_id', $userId)
            ->where('is_ai_photo_studio', true)
            ->orderBy('created_at', 'desc');

        if ($type === 'videos') {
            $query->where('response', 'APS-VIDEO')
                ->whereIn('status', [ImageStatusEnum::completed->value, ImageStatusEnum::processing->value]);
        } elseif ($type === 'images') {
            $query->where('response', 'APS')
                ->where('status', ImageStatusEnum::completed->value)
                ->whereNotNull('output');
        } else {
            $query->where(function ($q) {
                $q->where(function ($subQ) {
                    $subQ->where('response', 'APS')
                        ->where('status', ImageStatusEnum::completed->value)
                        ->whereNotNull('output');
                })->orWhere(function ($subQ) {
                    $subQ->where('response', 'APS-VIDEO')
                        ->whereIn('status', [ImageStatusEnum::completed->value, ImageStatusEnum::processing->value]);
                });
            });
        }

        $allImages = $query->get();

        $now = now();
        $today = $now->copy()->startOfDay();
        $yesterday = $now->copy()->subDay()->startOfDay();

        $thumbnails = [];

        foreach ($allImages as $image) {
            $isVideo = false;
            $isImage = false;
            $isProcessing = $image->status === ImageStatusEnum::processing->value;

            if ($image->response === 'APS-VIDEO') {
                $isVideo = true;
            } elseif (! empty($image->output)) {
                $extension = strtolower(pathinfo($image->output, PATHINFO_EXTENSION));
                $isVideo = in_array($extension, ['mp4', 'mov', 'avi', 'webm', 'mkv'], true);
                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
            }

            if (! $isVideo && ! $isImage) {
                $isImage = true;
            }

            if ($type === 'images' && ! $isImage) {
                continue;
            }
            if ($type === 'videos' && ! $isVideo) {
                continue;
            }

            $thumbnailUrl = $isProcessing ? '/themes/default/assets/img/loading.svg' : ThumbImage($image->output);
            $imageData = $image->toArray();
            $imageData['url'] = $isProcessing ? '/themes/default/assets/img/loading.svg' : $image->output;
            $imageData['thumbnail'] = $thumbnailUrl;

            $createdAt = $image->created_at;

            $imageData['is_today'] = $createdAt->greaterThanOrEqualTo($today) && $createdAt->lessThan($now->copy()->addDay()->startOfDay());
            $imageData['is_yesterday'] = $createdAt->greaterThanOrEqualTo($yesterday) && $createdAt->lessThan($today);
            $imageData['is_older'] = $createdAt->lessThan($yesterday);
            $imageData['format_date'] = $image->created_at->diffForHumans();
            $imageData['is_image'] = $isImage;
            $imageData['is_video'] = $isVideo;
            $imageData['is_processing'] = $isProcessing;

            $thumbnails[] = $imageData;
        }

        $total = count($thumbnails);
        $thumbnails = array_slice($thumbnails, ($page - 1) * $perPage, $perPage);

        return response()->json([
            'images'  => $thumbnails,
            'hasMore' => ($page * $perPage) < $total,
            'page'    => $page,
            'total'   => (int) ceil($total / $perPage),
        ]);
    }

    public function cropImage(Request $request): JsonResponse
    {
        $request->validate([
            'image_id'     => 'required|integer',
            'image_data'   => 'required|string',
            'width'        => 'nullable|numeric',
            'height'       => 'nullable|numeric',
            'aspect_ratio' => 'nullable|numeric',
        ]);

        $userId = Auth::id();
        $imageId = $request->input('image_id');

        $userOpenai = UserOpenai::where('id', $imageId)
            ->where('user_id', $userId)
            ->where('is_ai_photo_studio', true)
            ->first();

        if (! $userOpenai) {
            return response()->json(['error' => __('Image not found.')], 404);
        }

        $imageData = (string) $request->input('image_data');

        if (Str::startsWith($imageData, 'data:image')) {
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
        }

        $decodedImage = base64_decode($imageData);

        if ($decodedImage === false) {
            return response()->json(['error' => __('Invalid image data.')], 400);
        }

        $extension = 'png';
        $filename = 'cropped_' . Str::random(20) . '_' . time() . '.' . $extension;

        $imageStorage = Helper::settingTwo('ai_image_storage');

        try {
            if ($imageStorage === 'r2') {
                Storage::disk('r2')->put($filename, $decodedImage);
                $newUrl = Storage::disk('r2')->url($filename);
            } elseif ($imageStorage === 's3') {
                Storage::disk('s3')->put($filename, $decodedImage);
                $newUrl = Storage::disk('s3')->url($filename);
            } else {
                Storage::disk('public')->put($filename, $decodedImage);
                $newUrl = '/uploads/' . $filename;
            }

            $croppedImage = $userOpenai->replicate();
            $croppedImage->output = $newUrl;
            $croppedImage->created_at = now();
            $croppedImage->updated_at = now();

            $width = $request->input('width');
            $height = $request->input('height');
            $aspectRatio = $request->input('aspect_ratio');

            $existingPayload = $croppedImage->payload;
            if (is_string($existingPayload)) {
                $existingPayload = json_decode($existingPayload, true) ?? [];
            }
            $existingPayload = is_array($existingPayload) ? $existingPayload : [];

            $croppedImage->payload = array_merge($existingPayload, [
                'cropped_width'  => $width,
                'cropped_height' => $height,
                'aspect_ratio'   => $aspectRatio,
            ]);

            $croppedImage->save();

            return response()->json([
                'success'      => true,
                'message'      => __('Image cropped successfully.'),
                'image_id'     => $croppedImage->id,
                'url'          => $newUrl,
                'thumbnail'    => ThumbImage($newUrl),
                'width'        => $width,
                'height'       => $height,
                'aspect_ratio' => $aspectRatio,
            ]);
        } catch (Exception $e) {
            Log::error('AIPhotoshoot crop image error: ' . $e->getMessage());

            return response()->json(['error' => __('Failed to save cropped image.')], 500);
        }
    }

    public function removeImage(Request $request): JsonResponse
    {
        $request->validate([
            'image_id' => 'required|integer',
        ]);

        try {
            $record = UserOpenai::where('user_id', Auth::id())
                ->where('is_ai_photo_studio', true)
                ->findOrFail($request->input('image_id'));

            if ($record->output) {
                $relativePath = Str::after($record->output, '/uploads/');
                Storage::disk('public')->delete($relativePath);
            }

            $record->delete();

            return response()->json([
                'success' => true,
                'message' => __('Image removed successfully'),
            ]);
        } catch (Exception $e) {
            Log::error(__('Failed to remove image'), [
                'id'    => $request->input('image_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Failed to remove image'),
            ], 500);
        }
    }

    protected function getGenerationTitle(): string
    {
        return __('AI Photoshoot Generation');
    }

    protected function getSlugSuffix(): string
    {
        return 'aps';
    }

    protected function getPrompt(): string
    {
        return '';
    }

    protected function getImageUrls(): array
    {
        return [];
    }

    protected function getResponseKey(): string
    {
        return 'photoshoot';
    }
}
