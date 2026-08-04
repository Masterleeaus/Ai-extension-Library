<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Services;

use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;

class PlatformPermissionService
{
    /**
     * Required scopes/permissions per platform for automation to work.
     *
     * @return array<string, array{scopes: string[], dm_supported: bool, comment_reply_supported: bool, notes: string}>
     */
    public static function platformRequirements(): array
    {
        return [
            'instagram' => [
                'scopes'                  => ['instagram_manage_comments', 'instagram_manage_messages', 'pages_messaging'],
                'dm_supported'            => true,
                'comment_reply_supported' => true,
                'notes'                   => 'Requires Instagram Professional account connected via Facebook Page.',
                'rate_limit'              => '200 API calls per hour per user.',
                'webhook_supported'       => true,
            ],
            'facebook' => [
                'scopes'                  => ['pages_messaging', 'pages_manage_metadata', 'pages_read_engagement'],
                'dm_supported'            => true,
                'comment_reply_supported' => true,
                'notes'                   => 'Requires Facebook Page admin access.',
                'rate_limit'              => '200 API calls per hour per page.',
                'webhook_supported'       => true,
            ],
            'tiktok' => [
                'scopes'                  => ['comment.list', 'comment.create'],
                'dm_supported'            => false,
                'comment_reply_supported' => true,
                'notes'                   => 'DM not available via TikTok API. Only public comment replies supported.',
                'rate_limit'              => 'Varies by endpoint.',
                'webhook_supported'       => true,
            ],
            'linkedin' => [
                'scopes'                  => ['w_organization_social', 'r_organization_social'],
                'dm_supported'            => false,
                'comment_reply_supported' => true,
                'notes'                   => 'Limited automation support. Comment monitoring via polling only. DM not available via API.',
                'rate_limit'              => '100 requests per day per member.',
                'webhook_supported'       => false,
            ],
            'x' => [
                'scopes'                  => ['tweet.read', 'dm.write', 'dm.read', 'users.read'],
                'dm_supported'            => true,
                'comment_reply_supported' => true,
                'notes'                   => 'Requires X API Pro or Enterprise access for DM. Rate limits are strict.',
                'rate_limit'              => 'DM: 500 per day. Tweets: rate limited.',
                'webhook_supported'       => true,
            ],
            'twitter' => [
                'scopes'                  => ['tweet.read', 'dm.write', 'dm.read', 'users.read'],
                'dm_supported'            => true,
                'comment_reply_supported' => true,
                'notes'                   => 'Requires X API Pro or Enterprise access for DM. Rate limits are strict.',
                'rate_limit'              => 'DM: 500 per day. Tweets: rate limited.',
                'webhook_supported'       => true,
            ],
        ];
    }

    /**
     * Check if a platform account has the required permissions for automation.
     */
    public static function checkPermissions(SocialMediaPlatform $platform): array
    {
        $platformName = $platform->platform;
        $requirements = self::platformRequirements()[$platformName] ?? null;
        $credentials = (array) $platform->credentials;

        if (! $requirements) {
            return [
                'has_permissions'         => false,
                'missing_scopes'          => [],
                'dm_supported'            => false,
                'comment_reply_supported' => false,
                'notes'                   => 'This platform is not supported for automation.',
                'rate_limit'              => '',
            ];
        }

        $grantedScopes = $credentials['scopes'] ?? $credentials['permissions'] ?? [];
        if (is_string($grantedScopes)) {
            $grantedScopes = explode(',', $grantedScopes);
        }
        $grantedScopes = array_map('trim', $grantedScopes);

        $missingScopes = [];
        if (! empty($grantedScopes)) {
            $missingScopes = array_values(array_diff($requirements['scopes'], $grantedScopes));
        }

        // If no scopes info stored, we assume permissions might be present
        // (they'll fail at API call time if not)
        $hasPermissions = empty($grantedScopes) || empty($missingScopes);

        return [
            'has_permissions'         => $hasPermissions,
            'missing_scopes'          => $missingScopes,
            'dm_supported'            => $requirements['dm_supported'],
            'comment_reply_supported' => $requirements['comment_reply_supported'],
            'notes'                   => $requirements['notes'],
            'rate_limit'              => $requirements['rate_limit'],
            'webhook_supported'       => $requirements['webhook_supported'] ?? false,
        ];
    }

    /**
     * Get permission info for all user accounts (for the frontend).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAccountPermissions(iterable $accounts): array
    {
        $result = [];
        foreach ($accounts as $account) {
            $check = self::checkPermissions($account);
            $result[$account->id] = $check;
        }

        return $result;
    }
}
