<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GoogleBusinessProfilePinterestContractTest extends TestCase
{
    private function extensionPath(string $path): string
    {
        return dirname(__DIR__, 2) . '/' . ltrim($path, '/');
    }

    private function source(string $path): string
    {
        $absolute = $this->extensionPath($path);

        return is_file($absolute) ? (string) file_get_contents($absolute) : '';
    }

    private function config(string $path): array
    {
        $absolute = $this->extensionPath($path);

        return is_file($absolute) ? (array) require $absolute : [];
    }

    public function test_both_providers_are_registered_as_connected_channels_with_official_configs(): void
    {
        $enum = $this->source('System/Enums/PlatformEnum.php');
        $distribution = $this->config('config/distribution.php');
        $google = $this->config('config/google-business-profile.php');
        $pinterest = $this->config('config/pinterest.php');

        $this->assertStringContainsString("case google_business_profile = 'google-business-profile';", $enum);
        $this->assertStringContainsString("case pinterest = 'pinterest';", $enum);
        $this->assertStringContainsString('self::google_business_profile', $enum);
        $this->assertStringContainsString('self::pinterest', $enum);

        $this->assertSame('direct', data_get($google, 'destination.mode'));
        $this->assertTrue((bool) data_get($google, 'destination.adapter_available'));
        $this->assertSame('google-business-profile', data_get($google, 'destination.platform'));
        $this->assertTrue((bool) data_get($google, 'destination.approval_required'));
        $this->assertContains('https://www.googleapis.com/auth/business.manage', (array) ($google['scopes'] ?? []));

        $this->assertSame('direct', data_get($pinterest, 'destination.mode'));
        $this->assertTrue((bool) data_get($pinterest, 'destination.adapter_available'));
        $this->assertSame('pinterest', data_get($pinterest, 'destination.platform'));
        $this->assertTrue((bool) data_get($pinterest, 'destination.approval_required'));
        $this->assertContains('boards:read', (array) ($pinterest['scopes'] ?? []));
        $this->assertNotContains('boards:write', (array) ($pinterest['scopes'] ?? []));
        $this->assertContains('pins:read', (array) ($pinterest['scopes'] ?? []));
        $this->assertContains('pins:write', (array) ($pinterest['scopes'] ?? []));
        $this->assertContains('user_accounts:read', (array) ($pinterest['scopes'] ?? []));

        $this->assertArrayHasKey('google-business-profile', (array) ($distribution['destinations'] ?? []));
        $this->assertArrayHasKey('pinterest', (array) ($distribution['destinations'] ?? []));
    }

    public function test_oauth_uses_single_use_state_and_persists_only_encrypted_tokens(): void
    {
        $googleHelper = $this->source('System/Helpers/GoogleBusinessProfile.php');
        $pinterestHelper = $this->source('System/Helpers/Pinterest.php');
        $googleOauth = $this->source('System/Http/Controllers/Oauth/GoogleBusinessProfileController.php');
        $pinterestOauth = $this->source('System/Http/Controllers/Oauth/PinterestController.php');

        foreach ([$googleHelper, $pinterestHelper] as $helper) {
            $this->assertStringContainsString('Crypt::decryptString', $helper);
            $this->assertStringContainsString('Crypt::encryptString', $helper);
            $this->assertStringContainsString('access_token_encrypted', $helper);
            $this->assertStringContainsString('refresh_token_encrypted', $helper);
            $this->assertStringNotContainsString("\$credentials['access_token']", $helper);
            $this->assertStringNotContainsString("\$credentials['refresh_token']", $helper);
        }

        foreach ([$googleOauth, $pinterestOauth] as $controller) {
            $this->assertStringContainsString('Cache::pull', $controller);
            $this->assertStringContainsString('hash_equals', $controller);
            $this->assertStringContainsString('Auth::id()', $controller);
            $this->assertStringContainsString('access_token_encrypted', $controller);
            $this->assertStringContainsString('refresh_token_encrypted', $controller);
            $this->assertStringNotContainsString("'access_token' => \$accessToken", $controller);
            $this->assertStringNotContainsString("'refresh_token' => \$refreshToken", $controller);
        }
    }

    public function test_google_adapter_supports_discovery_posts_photos_reviews_performance_and_reconciliation(): void
    {
        $helper = $this->source('System/Helpers/GoogleBusinessProfile.php');
        $service = $this->source('System/Services/GoogleBusinessProfileService.php');
        $controller = $this->source('System/Http/Controllers/GoogleBusinessProfileController.php');

        foreach (['accounts', 'locations', 'createLocalPost', 'getLocalPost', 'createMedia', 'reviews', 'replyToReview', 'performance'] as $method) {
            $this->assertStringContainsString("function {$method}", $helper);
        }

        foreach (['readiness', 'publish', 'uploadPhoto', 'reconcile', 'reviews', 'replyToReview', 'reviewHandoff', 'performance'] as $method) {
            $this->assertStringContainsString("function {$method}", $service);
            $this->assertStringContainsString("function {$method}", $controller);
        }

        $this->assertStringContainsString('forVerticalDestination', $service);
        $this->assertStringContainsString("approval_status !== 'approved'", $service);
        $this->assertStringContainsString('request_hash', $service);
        $this->assertStringContainsString('rate_limit', $service);
        $this->assertStringContainsString('profile_version', $service);
        $this->assertStringContainsString('profile_provenance', $service);
        $this->assertStringContainsString("DB::table('ext_social_media_distribution_audits')", $service);
        $this->assertStringContainsString('human_handoff_required', $service);
        $this->assertStringNotContainsString('auto_reply', strtolower($service));
    }

    public function test_google_resources_are_live_verified_against_the_selected_account_and_location(): void
    {
        $guard = $this->source('System/Services/GoogleBusinessProfileResourceGuard.php');
        $controller = $this->source('System/Http/Controllers/GoogleBusinessProfileController.php');

        $this->assertStringContainsString('function resolveLocation', $guard);
        $this->assertStringContainsString('function assertReviewName', $guard);
        $this->assertStringContainsString("->locations(\$account, \$accountName)", $guard);
        $this->assertStringContainsString("'account_location_name'", $guard);
        $this->assertStringContainsString("'performance_location_name'", $guard);
        $this->assertStringContainsString('location_not_authorized_for_account', $guard);
        $this->assertStringContainsString('GoogleBusinessProfileResourceGuard', $controller);
        $this->assertStringContainsString('resolveLocation', $controller);
        $this->assertStringContainsString('assertReviewName', $controller);
    }

    public function test_pinterest_adapter_supports_boards_pins_product_links_analytics_and_reconciliation(): void
    {
        $helper = $this->source('System/Helpers/Pinterest.php');
        $service = $this->source('System/Services/PinterestService.php');
        $controller = $this->source('System/Http/Controllers/PinterestController.php');

        foreach (['userAccount', 'boards', 'createPin', 'getPin', 'pinAnalytics'] as $method) {
            $this->assertStringContainsString("function {$method}", $helper);
        }

        foreach (['readiness', 'boards', 'publish', 'reconcile', 'analytics', 'engagementHandoff'] as $method) {
            $this->assertStringContainsString("function {$method}", $service);
            $this->assertStringContainsString("function {$method}", $controller);
        }

        $this->assertStringContainsString('forVerticalDestination', $service);
        $this->assertStringContainsString("approval_status !== 'approved'", $service);
        $this->assertStringContainsString('TYPE_PRODUCT_OFFER', $service);
        $this->assertStringContainsString("'link'", $service);
        $this->assertStringContainsString('request_hash', $service);
        $this->assertStringContainsString('rate_limit', $service);
        $this->assertStringContainsString('profile_version', $service);
        $this->assertStringContainsString('profile_provenance', $service);
        $this->assertStringContainsString("DB::table('ext_social_media_distribution_audits')", $service);
        $this->assertStringContainsString('human_handoff_required', $service);
        $this->assertStringNotContainsString('auto_reply', strtolower($service));
    }

    public function test_secret_pinterest_boards_are_removed_and_cannot_be_selected(): void
    {
        $guard = $this->source('System/Services/PinterestBoardGuard.php');
        $controller = $this->source('System/Http/Controllers/PinterestController.php');

        $this->assertStringContainsString('function filterVisibleBoards', $guard);
        $this->assertStringContainsString('function synchroniseDiscovery', $guard);
        $this->assertStringContainsString('function assertWritableBoard', $guard);
        $this->assertStringContainsString("'SECRET'", $guard);
        $this->assertStringContainsString('secret_board_not_publishable', $guard);
        $this->assertStringContainsString('PinterestBoardGuard', $controller);
        $this->assertStringContainsString('synchroniseDiscovery', $controller);
        $this->assertStringContainsString('assertWritableBoard', $controller);
    }

    public function test_both_adapters_consume_exactly_nine_vertical_profiles_and_generic_fallback(): void
    {
        $verticals = $this->config('config/vertical-distribution.php');
        $profiles = (array) ($verticals['verticals'] ?? []);

        $this->assertCount(9, $profiles);
        $this->assertSame([
            'field-home-services',
            'accommodation',
            'real-estate',
            'salons-personal-care',
            'fitness-membership',
            'automotive-services',
            'ecommerce-retail',
            'hire-rental',
            'booking-capacity',
        ], array_keys($profiles));
        $this->assertContains('facilities-maintenance', (array) data_get($profiles, 'field-home-services.subtypes', []));
        $this->assertArrayNotHasKey('facilities-management', $profiles);
        $this->assertSame('generic-business', data_get($verticals, 'generic_profile.slug'));

        foreach (['google-business-profile', 'pinterest'] as $destination) {
            foreach ($profiles as $profile) {
                $this->assertArrayHasKey($destination, (array) ($profile['destination_suitability'] ?? []));
            }
        }
    }

    public function test_routes_are_authenticated_tenant_scoped_locked_and_no_blade_page_is_added(): void
    {
        $provider = $this->source('System/SocialMediaServiceProvider.php');
        $googleController = $this->source('System/Http/Controllers/GoogleBusinessProfileController.php');
        $pinterestController = $this->source('System/Http/Controllers/PinterestController.php');
        $docs = $this->source('docs/TITAN-REACH-GOOGLE-PINTEREST-FILE-MAP.md');

        foreach ([
            'redirect/google-business-profile',
            'redirect/pinterest',
            'google-business-profile/readiness',
            'google-business-profile/publish',
            'google-business-profile/photo',
            'google-business-profile/reconcile',
            'google-business-profile/reviews',
            'google-business-profile/performance',
            'pinterest/readiness',
            'pinterest/boards',
            'pinterest/publish',
            'pinterest/reconcile',
            'pinterest/analytics',
        ] as $routeFragment) {
            $this->assertStringContainsString($routeFragment, $provider);
        }

        foreach ([$googleController, $pinterestController] as $controller) {
            $this->assertStringContainsString("->where('user_id', Auth::id())", $controller);
            $this->assertStringContainsString('Cache::lock', $controller);
            $this->assertStringContainsString('$item->refresh()', $controller);
            $this->assertStringContainsString('abort_if((int) $item->user_id !== (int) Auth::id(), 404)', $controller);
        }

        $this->assertStringContainsString('No new Blade page', $docs);
        $this->assertStringContainsString('issue #274', strtolower($docs));
    }
}
