<?php

namespace App\Extensions\SocialMedia\System\Enums;

use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use Illuminate\Support\Facades\Auth;

enum PlatformEnum: string
{
    case facebook = 'facebook';

    case x = 'x';

    case instagram = 'instagram';

    case linkedin = 'linkedin';

    case tiktok = 'tiktok';

    case youtube = 'youtube';

    case youtube_shorts = 'youtube-shorts';

    case ebay = 'ebay';

    public function contentCharacterLength(): int
    {
        return match ($this) {
            self::facebook       => 63206,
            self::instagram      => 2200,
            self::x              => 280,
            self::linkedin       => 2900,
            self::tiktok         => 2900,
            self::youtube        => 5000,
            self::youtube_shorts => 150,
            self::ebay           => 500000,
        };
    }

    public static function toArray(): array
    {
        return array_map(static fn (self $platform) => $platform->value, self::all());
    }

    public static function all(): array
    {
        return [
            self::facebook,
            self::instagram,
            self::x,
            self::linkedin,
            self::tiktok,
            self::youtube,
            self::youtube_shorts,
        ];
    }

    public static function channels(): array
    {
        return [...self::all(), self::ebay];
    }

    public static function channelValues(): array
    {
        return array_map(static fn (self $platform) => $platform->value, self::channels());
    }

    public function platform()
    {
        $platforms = SocialMediaPlatform::query()
            ->where('user_id', Auth::id())
            ->whereIn('platform', self::channelValues())
            ->orderByDesc('expires_at')
            ->get();

        return match ($this) {
            self::facebook       => $platforms->where('platform', self::facebook->value)->first(),
            self::instagram      => $platforms->where('platform', self::instagram->value)->first(),
            self::x              => $platforms->where('platform', self::x->value)->first(),
            self::linkedin       => $platforms->where('platform', self::linkedin->value)->first(),
            self::tiktok         => $platforms->where('platform', self::tiktok->value)->first(),
            self::youtube        => $platforms->where('platform', self::youtube->value)->first(),
            self::youtube_shorts => $platforms->where('platform', self::youtube_shorts->value)->first(),
            self::ebay           => $platforms->where('platform', self::ebay->value)->first(),
        };
    }

    public function credentials(): array
    {
        return match ($this) {
            self::facebook => [
                'facebook_app_id'         => setting('FACEBOOK_APP_ID'),
                'facebook_app_secret'     => setting('FACEBOOK_APP_SECRET'),
                'facebook_webhook_secret' => setting('FACEBOOK_WEBHOOK_SECRET', 'default-password'),
            ],
            self::instagram => [
                'instagram_app_id'         => setting('INSTAGRAM_APP_ID'),
                'instagram_app_secret'     => setting('INSTAGRAM_APP_SECRET'),
                'instagram_webhook_secret' => setting('INSTAGRAM_WEBHOOK_SECRET', 'default-password'),
            ],
            self::x => [
                'x_api_key'             => setting('X_API_KEY'),
                'x_api_secret'          => setting('X_API_SECRET'),
                'x_access_token'        => setting('X_ACCESS_TOKEN'),
                'x_access_token_secret' => setting('X_ACCESS_TOKEN_SECRET'),
                'x_client_id'           => setting('X_CLIENT_ID'),
                'x_client_secret'       => setting('X_CLIENT_SECRET'),
            ],
            self::linkedin => [
                'linkedin_app_id'     => setting('LINKEDIN_APP_ID'),
                'linkedin_app_secret' => setting('LINKEDIN_APP_SECRET'),
            ],
            self::tiktok => [
                'tiktok_app_id'       => setting('TIKTOK_APP_ID'),
                'tiktok_app_key'      => setting('TIKTOK_APP_KEY'),
                'tiktok_app_secret'   => setting('TIKTOK_APP_SECRET'),
            ],
            self::youtube => [
                'youtube_client_id'     => setting('YOUTUBE_CLIENT_ID'),
                'youtube_client_secret' => setting('YOUTUBE_CLIENT_SECRET'),
            ],
            self::youtube_shorts => [
                'youtube_client_id'     => setting('YOUTUBE_CLIENT_ID'),
                'youtube_client_secret' => setting('YOUTUBE_CLIENT_SECRET'),
            ],
            self::ebay => [
                'ebay_client_id'     => setting('EBAY_CLIENT_ID'),
                'ebay_client_secret' => setting('EBAY_CLIENT_SECRET'),
                'ebay_redirect_uri'  => setting('EBAY_REDIRECT_URI'),
            ],
        };
    }

    public function webhookUrl(): ?string
    {
        return match ($this) {
            self::facebook  => url('/social-media/webhook/facebook'),
            self::instagram => url('/social-media/webhook/instagram'),
            self::x         => url('/social-media/automation/webhook/x'),
            self::tiktok    => url('/social-media/automation/webhook/tiktok'),
            default         => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::facebook       => 'Facebook',
            self::instagram      => 'Instagram',
            self::x              => 'X',
            self::linkedin       => 'LinkedIn',
            self::tiktok         => 'TikTok',
            self::youtube        => 'YouTube',
            self::youtube_shorts => 'YouTube Shorts',
            self::ebay           => 'eBay',
        };
    }
}
