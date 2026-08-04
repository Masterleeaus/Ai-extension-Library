<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Http\Controllers;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\Facebook;
use App\Extensions\SocialMedia\System\Helpers\Instagram;
use App\Extensions\SocialMedia\System\Helpers\X as XHelper;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMedia\System\Models\SocialMediaPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AutomationBuilderController extends Controller
{
    public function fetchPosts(Request $request): JsonResponse
    {
        $request->validate([
            'platform_id' => 'required|integer',
            'search'      => 'nullable|string|max:255',
        ]);

        $platform = SocialMediaPlatform::query()
            ->where('id', $request->platform_id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $platform) {
            return response()->json([
                'status' => 'error',
                'data'   => [],
            ]);
        }

        $posts = $this->fetchFromPlatformApi($platform, $request->search);

        if ($posts === null) {
            $posts = $this->fetchFromLocalDb($request);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $posts,
        ]);
    }

    private function fetchFromPlatformApi(SocialMediaPlatform $platform, ?string $search): ?Collection
    {
        if (! $platform->isConnected()) {
            return null;
        }

        $platformEnum = PlatformEnum::tryFrom($platform->platform);
        $accessToken = data_get($platform->credentials, 'access_token');
        $platformId = data_get($platform->credentials, 'platform_id');

        if (! $accessToken || ! $platformId) {
            return null;
        }

        try {
            $posts = match ($platformEnum) {
                PlatformEnum::instagram => $this->fetchInstagramPosts($accessToken, $platformId),
                PlatformEnum::facebook  => $this->fetchFacebookPosts($accessToken, $platformId),
                PlatformEnum::x         => $this->fetchXPosts($accessToken, $platformId),
                default                 => null,
            };
        } catch (Throwable $e) {
            Log::warning('AutomationBuilder: Failed to fetch posts from platform API', [
                'platform' => $platform->platform,
                'error'    => $e->getMessage(),
            ]);

            return null;
        }

        if ($posts === null) {
            return null;
        }

        if ($search) {
            $term = mb_strtolower($search);
            $posts = $posts->filter(fn (array $post) => str_contains(mb_strtolower($post['content'] ?? ''), $term));
        }

        return $posts->values();
    }

    private function fetchInstagramPosts(string $accessToken, string $igId): Collection
    {
        $instagram = new Instagram(accessToken: $accessToken);
        $response = $instagram->getMedia($igId, 50);

        if ($response->failed()) {
            return collect();
        }

        return collect($response->json('data', []))->map(fn (array $item) => [
            'id'               => $item['id'],
            'platform_post_id' => $item['id'],
            'content'          => $item['caption'] ?? '',
            'image'            => $item['media_url'] ?? $item['thumbnail_url'] ?? null,
            'post_type'        => match (strtolower($item['media_type'] ?? 'image')) {
                'carousel_album' => 'carousel',
                default          => strtolower($item['media_type'] ?? 'image'),
            },
            'created_at'       => $item['timestamp'] ?? null,
        ]);
    }

    private function fetchFacebookPosts(string $accessToken, string $pageId): Collection
    {
        $facebook = new Facebook(accessToken: $accessToken);
        $response = $facebook->getPageFeed($pageId, 50);

        if ($response->failed()) {
            return collect();
        }

        return collect($response->json('data', []))->map(fn (array $item) => [
            'id'               => $item['id'],
            'platform_post_id' => $item['id'],
            'content'          => $item['message'] ?? '',
            'image'            => $item['full_picture'] ?? null,
            'post_type'        => match ($item['type'] ?? 'status') {
                'status' => 'post',
                'added_photos', 'photo' => 'photo',
                'added_video', 'video' => 'video',
                default  => $item['type'] ?? 'post',
            },
            'created_at'       => $item['created_time'] ?? null,
        ]);
    }

    private function fetchXPosts(string $accessToken, string $userId): Collection
    {
        $x = new XHelper(accessToken: $accessToken);
        $response = $x->getUserTweets($userId, 50);

        if ($response->failed()) {
            throw new RuntimeException('X API error: ' . ($response->json('title') ?? $response->status()));
        }

        $mediaMap = collect($response->json('includes.media', []))
            ->keyBy('media_key');

        return collect($response->json('data', []))->map(function (array $item) use ($mediaMap): array {
            $mediaKey = data_get($item, 'attachments.media_keys.0');
            $media = $mediaKey ? $mediaMap->get($mediaKey) : null;
            $mediaType = $media['type'] ?? null;
            $image = $media['url'] ?? $media['preview_image_url'] ?? null;

            return [
                'id'               => $item['id'],
                'platform_post_id' => $item['id'],
                'content'          => $item['text'] ?? '',
                'image'            => $image,
                'post_type'        => match ($mediaType) {
                    'photo'       => 'photo',
                    'video', 'animated_gif' => 'video',
                    default       => 'post',
                },
                'created_at'       => $item['created_at'] ?? null,
            ];
        });
    }

    private function fetchFromLocalDb(Request $request): Collection
    {
        return SocialMediaPost::query()
            ->where('user_id', Auth::id())
            ->where('social_media_platform_id', $request->platform_id)
            ->where('status', 'published')
            ->when($request->search, function ($query, $search) {
                $query->where('content', 'like', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'content', 'image', 'post_id as platform_post_id', 'post_type', 'created_at']);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $path = $request->file('image')->store('automation-images', 'public');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'path' => $path,
                'url'  => asset('uploads/' . $path),
            ],
        ]);
    }
}
