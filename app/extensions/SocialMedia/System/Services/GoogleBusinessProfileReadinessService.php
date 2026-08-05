<?php

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\GoogleBusinessProfile;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class GoogleBusinessProfileReadinessService
{
    private const DESTINATION = 'google-business-profile';

    public function __construct(private readonly GoogleBusinessProfile $google) {}

    public function readiness(User $user, SocialMediaPlatform $account): array
    {
        $this->assertAccount($user, $account);
        $accountsResponse = $this->google->accounts($account);

        if (! $accountsResponse->successful()) {
            throw new RuntimeException('Google Business Profile account discovery failed.');
        }

        $providerAccounts = array_slice(
            array_values((array) $accountsResponse->json('accounts', [])),
            0,
            20
        );
        $locations = [];
        $locationRateLimits = [];

        foreach ($providerAccounts as $providerAccount) {
            $accountName = trim((string) ($providerAccount['name'] ?? ''));

            if (! preg_match('#^accounts/[^/]+$#', $accountName)
                || count($locations) >= 100) {
                continue;
            }

            $response = $this->google->locations($account, $accountName);
            $locationRateLimits[] = $this->google->rateLimit($response);

            if (! $response->successful()) {
                continue;
            }

            foreach ((array) $response->json('locations', []) as $location) {
                $summary = $this->locationSummary($accountName, (array) $location);

                if ($summary['name'] !== null && $summary['v4_name'] !== null) {
                    $locations[] = $summary;
                }

                if (count($locations) >= 100) {
                    break;
                }
            }
        }

        $credentials = (array) $account->credentials;
        $manageGranted = in_array(
            'https://www.googleapis.com/auth/business.manage',
            (array) ($credentials['authorized_scopes'] ?? []),
            true
        );
        $postableLocations = array_values(array_filter(
            $locations,
            static fn (array $location): bool =>
                ($location['can_operate_local_post'] ?? null) === true
        ));
        $providerCapabilities = [
            'account_discovery' => $providerAccounts !== [],
            'location_discovery' => $locations !== [],
            'publish' => $manageGranted && $postableLocations !== [],
            'photos' => $manageGranted && $locations !== [],
            'reviews' => $manageGranted && $locations !== [],
            'review_reply' => $manageGranted && $locations !== [],
            'analytics' => $manageGranted && $locations !== [],
            'status_reconciliation' => $manageGranted && $locations !== [],
        ];
        $result = [
            'ready' => $providerCapabilities['publish'],
            'accounts' => $providerAccounts,
            'locations' => $locations,
            'postable_locations' => $postableLocations,
            'provider_capabilities' => $providerCapabilities,
            'rate_limit' => [
                'accounts' => $this->google->rateLimit($accountsResponse),
                'locations' => array_slice($locationRateLimits, -20),
            ],
            'checked_at' => now()->toIso8601String(),
        ];

        $credentials['account_names'] = array_values(array_filter(array_map(
            static fn (array $providerAccount): ?string => isset($providerAccount['name'])
                ? (string) $providerAccount['name']
                : null,
            $providerAccounts
        )));
        $credentials['locations'] = $locations;
        $credentials['provider_capabilities'] = $providerCapabilities;
        $credentials['health'] = [
            'status' => $result['ready'] ? 'healthy' : 'limited',
            'checked_at' => $result['checked_at'],
            'accounts_count' => count($providerAccounts),
            'locations_count' => count($locations),
            'postable_locations_count' => count($postableLocations),
            'rate_limit' => $result['rate_limit'],
        ];
        $account->update(['credentials' => $credentials]);
        $account->refresh();
        $this->audit($user, $account, $result);

        return $result;
    }

    private function locationSummary(string $accountName, array $location): array
    {
        $locationName = trim((string) ($location['name'] ?? ''), '/');
        $locationId = preg_match('#^locations/([^/]+)$#', $locationName, $matches)
            ? $matches[1]
            : null;

        return [
            'name' => $locationId ? 'locations/' . $locationId : null,
            'account_name' => $accountName,
            'v4_name' => $locationId ? $accountName . '/locations/' . $locationId : null,
            'title' => $location['title'] ?? null,
            'store_code' => $location['storeCode'] ?? null,
            'website_uri' => $location['websiteUri'] ?? null,
            'primary_category' => data_get($location, 'categories.primaryCategory.displayName'),
            'place_id' => data_get($location, 'metadata.placeId'),
            'maps_uri' => data_get($location, 'metadata.mapsUri'),
            'can_operate_local_post' => data_get($location, 'metadata.canOperateLocalPost'),
        ];
    }

    private function assertAccount(User $user, SocialMediaPlatform $account): void
    {
        if ((int) $account->user_id !== (int) $user->getKey()
            || (string) $account->platform !== PlatformEnum::google_business_profile->value
            || ! $account->isConnected()) {
            throw new RuntimeException('A connected Google Business Profile account is required.');
        }
    }

    private function audit(User $user, SocialMediaPlatform $account, array $result): void
    {
        if (! Schema::hasTable('ext_social_media_distribution_audits')) {
            return;
        }

        DB::table('ext_social_media_distribution_audits')->insert([
            'user_id' => $user->getKey(),
            'social_media_platform_id' => $account->getKey(),
            'destination' => self::DESTINATION,
            'action' => 'google_business_profile_readiness',
            'snapshot' => json_encode([
                'ready' => $result['ready'],
                'account_count' => count($result['accounts']),
                'location_count' => count($result['locations']),
                'postable_location_count' => count($result['postable_locations']),
                'provider_capabilities' => $result['provider_capabilities'],
                'rate_limit' => $result['rate_limit'],
                'checked_at' => $result['checked_at'],
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
