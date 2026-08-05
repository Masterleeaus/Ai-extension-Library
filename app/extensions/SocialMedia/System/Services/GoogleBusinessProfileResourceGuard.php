<?php

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Helpers\GoogleBusinessProfile;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use InvalidArgumentException;
use RuntimeException;

class GoogleBusinessProfileResourceGuard
{
    public function __construct(private readonly GoogleBusinessProfile $google) {}

    public function resolveLocation(
        SocialMediaPlatform $account,
        string $accountName,
        string $locationName,
        bool $requireLocalPost = false
    ): array {
        $accountName = $this->normaliseAccountName($accountName);
        $performanceLocationName = $this->normaliseLocationName($locationName, $accountName);
        $knownAccounts = array_values(array_filter(array_map(
            static fn ($name): ?string => is_string($name) ? trim($name) : null,
            (array) data_get($account->credentials, 'account_names', [])
        )));

        if (! in_array($accountName, $knownAccounts, true)) {
            throw new InvalidArgumentException('google_account_not_authorized');
        }

        $response = $this->google->locations($account, $accountName);

        if (! $response->successful()) {
            throw new RuntimeException('google_location_discovery_failed');
        }

        $location = collect((array) $response->json('locations', []))
            ->map(static fn ($item): array => (array) $item)
            ->first(static fn (array $item): bool => trim((string) ($item['name'] ?? '')) === $performanceLocationName);

        if (! is_array($location)) {
            throw new InvalidArgumentException('location_not_authorized_for_account');
        }

        if ($requireLocalPost
            && data_get($location, 'metadata.canOperateLocalPost') === false) {
            throw new RuntimeException('location_cannot_publish_local_posts');
        }

        $locationId = basename($performanceLocationName);

        if ($locationId === '' || str_contains($locationId, '/')) {
            throw new InvalidArgumentException('location_not_authorized_for_account');
        }

        return [
            'account_name' => $accountName,
            'performance_location_name' => $performanceLocationName,
            'account_location_name' => $accountName . '/locations/' . $locationId,
            'location' => $location,
            'rate_limit' => $this->google->rateLimit($response),
        ];
    }

    public function assertLocalPostName(array $resources, string $localPostName): string
    {
        $localPostName = trim($localPostName);
        $prefix = rtrim((string) ($resources['account_location_name'] ?? ''), '/')
            . '/localPosts/';

        if ($localPostName === ''
            || $prefix === '/localPosts/'
            || ! str_starts_with($localPostName, $prefix)
            || trim(substr($localPostName, strlen($prefix))) === '') {
            throw new InvalidArgumentException('local_post_not_authorized_for_location');
        }

        return $localPostName;
    }

    public function assertReviewName(array $resources, string $reviewName): string
    {
        $reviewName = trim($reviewName);
        $prefix = rtrim((string) ($resources['account_location_name'] ?? ''), '/') . '/reviews/';

        if ($reviewName === ''
            || $prefix === '/reviews/'
            || ! str_starts_with($reviewName, $prefix)
            || trim(substr($reviewName, strlen($prefix))) === '') {
            throw new InvalidArgumentException('review_not_authorized_for_location');
        }

        return $reviewName;
    }

    private function normaliseAccountName(string $accountName): string
    {
        $accountName = trim($accountName, " \t\n\r\0\x0B/");

        if (! preg_match('#^accounts/[^/]+$#', $accountName)) {
            throw new InvalidArgumentException('google_account_not_authorized');
        }

        return $accountName;
    }

    private function normaliseLocationName(string $locationName, string $accountName): string
    {
        $locationName = trim($locationName, " \t\n\r\0\x0B/");

        if (preg_match('#^locations/([^/]+)$#', $locationName, $matches)) {
            return 'locations/' . $matches[1];
        }

        if (preg_match('#^accounts/([^/]+)/locations/([^/]+)$#', $locationName, $matches)) {
            if ($accountName !== 'accounts/' . $matches[1]) {
                throw new InvalidArgumentException('location_not_authorized_for_account');
            }

            return 'locations/' . $matches[2];
        }

        throw new InvalidArgumentException('location_not_authorized_for_account');
    }
}
