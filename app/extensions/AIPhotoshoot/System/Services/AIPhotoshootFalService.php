<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Services;

use App\Domains\Entity\Enums\EntityEnum;
use App\Helpers\Classes\ApiHelper;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AIPhotoshootFalService implements AIPhotoshootProviderInterface
{
    public const GENERATE_ENDPOINT = 'https://queue.fal.run/fal-ai/nano-banana-pro/edit';

    public const TEXT_TO_IMAGE_ENDPOINT = 'https://queue.fal.run/fal-ai/nano-banana-pro';

    public const CHECK_ENDPOINT = 'https://queue.fal.run/fal-ai/nano-banana-pro/requests/%s';

    public const VIDEO_BASE_ENDPOINT = 'https://queue.fal.run/fal-ai/%s';

    public const VIDEO_CHECK_ENDPOINT = 'https://queue.fal.run/fal-ai/%s/requests/%s';

    public function entityEnum(): EntityEnum
    {
        return EntityEnum::NANO_BANANA_PRO_EDIT;
    }

    public static function getVideoModel(): string
    {
        return setting('ai-photoshoot-video-default-model', EntityEnum::VEO_3_1_IMAGE_TO_VIDEO->value);
    }

    public static function getVideoModelBasePath(): string
    {
        $model = self::getVideoModel();
        $parts = explode('/', $model);

        return $parts[0] ?? $model;
    }

    public static function generateVideo(string $prompt, string $imageUrl): string
    {
        set_time_limit(0);
        ini_set('max_execution_time', 540);

        $model = self::getVideoModel();
        $url = sprintf(self::VIDEO_BASE_ENDPOINT, $model);

        $request = [
            'prompt'    => $prompt,
            'image_url' => $imageUrl,
        ];

        $http = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Key ' . ApiHelper::setFalAIKey(),
        ])->post($url, $request);

        if (($http->status() === 200) && $requestId = $http->json('request_id')) {
            return $requestId;
        }

        $detail = $http->json('detail');

        throw new RuntimeException(__($detail ?: 'Check your FAL API key.'));
    }

    public static function checkVideo(string $uuid): ?array
    {
        set_time_limit(0);
        ini_set('max_execution_time', 540);

        $modelBasePath = self::getVideoModelBasePath();
        $url = sprintf(self::VIDEO_CHECK_ENDPOINT, $modelBasePath, $uuid);

        $http = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Key ' . ApiHelper::setFalAIKey(),
        ])->get($url);

        $detail = $http->json('detail');
        $detailString = is_string($detail) ? $detail : (is_array($detail) ? json_encode($detail) : '');
        if ($detailString && str_contains(strtolower($detailString), 'in progress')) {
            return null;
        }

        if ($http->status() === 422) {
            $error = is_array($detail) ? ($detail[0]['msg'] ?? json_encode($detail)) : $detail;

            throw new RuntimeException(__($error ?: 'Unable to process request.'));
        }

        if ($http->status() >= 400) {
            $error = is_string($detail) ? $detail : ($http->json('message') ?? $http->json('error') ?? null);

            throw new RuntimeException(__($error ?: 'Video generation failed. Status: ' . $http->status()));
        }

        $error = $http->json('error');
        if ($error) {
            $errorMsg = is_string($error) ? $error : json_encode($error);

            throw new RuntimeException(__($errorMsg));
        }

        $video = $http->json('video');
        if ($video) {
            $videoUrl = is_array($video) ? data_get($video, 'url') : $video;

            if ($videoUrl) {
                return [
                    'video_url' => $videoUrl,
                    'type'      => 'video',
                ];
            }
        }

        $videoUrl = $http->json('video_url') ?? $http->json('output.video_url') ?? $http->json('result.video_url');
        if ($videoUrl) {
            return [
                'video_url' => $videoUrl,
                'type'      => 'video',
            ];
        }

        return null;
    }

    public function generate(
        string $prompt,
        array $imageUrls,
        int $numImages,
        string $aspectRatio,
        string $resolution
    ): string {
        set_time_limit(0);
        ini_set('max_execution_time', 540);

        $hasImages = ! empty($imageUrls);

        $request = [
            'prompt'     => $prompt,
            'num_images' => $numImages,
        ];

        if ($hasImages) {
            $request['image_urls'] = $imageUrls;
        }

        if ($aspectRatio !== '' && $aspectRatio !== 'auto') {
            $request['aspect_ratio'] = $aspectRatio;
        }

        if ($resolution !== '') {
            $request['resolution'] = $resolution;
        }

        $endpoint = $hasImages ? self::GENERATE_ENDPOINT : self::TEXT_TO_IMAGE_ENDPOINT;

        $http = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Key ' . ApiHelper::setFalAIKey(),
        ])->post($endpoint, $request);

        if (($http->status() === 200) && $requestId = $http->json('request_id')) {
            return $requestId;
        }

        $detail = $http->json('detail');

        throw new RuntimeException(__($detail ?: 'Check your FAL API key.'));
    }

    public static function generateFromText(string $prompt, array $options = []): string
    {
        set_time_limit(0);
        ini_set('max_execution_time', 540);

        $request = array_merge(['prompt' => $prompt], $options);

        $http = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Key ' . ApiHelper::setFalAIKey(),
        ])->post(self::TEXT_TO_IMAGE_ENDPOINT, $request);

        if (($http->status() === 200) && $requestId = $http->json('request_id')) {
            return $requestId;
        }

        $detail = $http->json('detail');

        throw new RuntimeException(__($detail ?: 'Failed to generate image. Check your FAL API key.'));
    }

    public function check(string $uuid): ?array
    {
        set_time_limit(0);
        ini_set('max_execution_time', 540);

        $url = sprintf(self::CHECK_ENDPOINT, $uuid);

        $http = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Key ' . ApiHelper::setFalAIKey(),
        ])->get($url);

        // Check if request is still in progress (check this FIRST before treating as error)
        $detail = $http->json('detail');
        $detailString = is_string($detail) ? $detail : (is_array($detail) ? json_encode($detail) : '');
        if ($detailString && str_contains(strtolower($detailString), 'in progress')) {
            return null;
        }

        if ($http->status() === 422) {
            $error = is_array($detail) ? ($detail[0]['msg'] ?? json_encode($detail)) : $detail;

            throw new RuntimeException(__($error ?: 'Unable to process request.'));
        }

        if ($http->status() >= 400) {
            $error = is_string($detail) ? $detail : ($http->json('message') ?? $http->json('error') ?? null);

            throw new RuntimeException(__($error ?: 'Generation failed. Status: ' . $http->status()));
        }

        $error = $http->json('error');
        if ($error) {
            $errorMsg = is_string($error) ? $error : json_encode($error);

            throw new RuntimeException(__($errorMsg));
        }

        $images = $http->json('images');

        if (! $images || ! is_array($images) || count($images) === 0) {
            return null;
        }

        if (is_string($images[0])) {
            return [
                'images' => $images,
                'size'   => $http->json('size') ?: 'unknown',
            ];
        }

        if (is_array($images[0])) {
            $firstImage = $images[0];

            return [
                'images' => array_map(static function ($image) {
                    return data_get($image, 'url');
                }, $images),
                'size'  => (data_get($firstImage, 'width') ?: 'unknown') . 'x' . (data_get($firstImage, 'height') ?: 'unknown'),
            ];
        }

        return null;
    }
}
