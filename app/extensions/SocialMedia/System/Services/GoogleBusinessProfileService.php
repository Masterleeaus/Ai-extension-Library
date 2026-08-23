<?php

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\GoogleBusinessProfile;
use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class GoogleBusinessProfileService
{
    private const DESTINATION = 'google-business-profile';

    private const IDEMPOTENCY_HISTORY_LIMIT = 10;

    public function __construct(
        private readonly GoogleBusinessProfile $google,
        private readonly DistributionCapabilityService $capabilities
    ) {}

    public function readiness(User $user, SocialMediaPlatform $account): array
    {
        $this->assertAccount($user, $account);
        $accountsResponse = $this->google->accounts($account);
        $this->assertSuccessful($accountsResponse, 'Google Business Profile account discovery failed.');
        $accounts = array_slice(
            array_values((array) $accountsResponse->json('accounts', [])),
            0,
            20
        );
        $locations = [];
        $locationRateLimits = [];

        foreach ($accounts as $providerAccount) {
            $accountName = trim((string) ($providerAccount['name'] ?? ''));

            if ($accountName === '' || count($locations) >= 100) {
                continue;
            }

            $response = $this->google->locations($account, $accountName);
            $locationRateLimits[] = $this->google->rateLimit($response);

            if (! $response->successful()) {
                continue;
            }

            foreach ((array) $response->json('locations', []) as $location) {
                $locations[] = $this->locationSummary((array) $location);

                if (count($locations) >= 100) {
                    break;
                }
            }
        }

        $credentials = (array) $account->credentials;
        $scopeGranted = in_array(
            'https://www.googleapis.com/auth/business.manage',
            (array) ($credentials['authorized_scopes'] ?? []),
            true
        );
        $providerCapabilities = [
            'account_discovery' => $accounts !== [],
            'location_discovery' => $locations !== [],
            'publish' => $scopeGranted && $locations !== [],
            'photos' => $scopeGranted && $locations !== [],
            'reviews' => $scopeGranted && $locations !== [],
            'review_reply' => $scopeGranted && $locations !== [],
            'analytics' => $scopeGranted && $locations !== [],
            'status_reconciliation' => $scopeGranted && $locations !== [],
        ];
        $result = [
            'ready' => $providerCapabilities['publish'],
            'accounts' => $accounts,
            'locations' => $locations,
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
            $accounts
        )));
        $credentials['locations'] = $locations;
        $credentials['provider_capabilities'] = $providerCapabilities;
        $credentials['health'] = [
            'status' => $result['ready'] ? 'healthy' : 'limited',
            'checked_at' => $result['checked_at'],
            'accounts_count' => count($accounts),
            'locations_count' => count($locations),
            'rate_limit' => $result['rate_limit'],
        ];
        $account->update(['credentials' => $credentials]);
        $this->audit($user, $account, 'google_business_profile_readiness', $result);

        return $result;
    }

    public function publish(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        array $input,
        string $idempotencyKey
    ): array {
        $capability = $this->assertContext($user, $item, $account, 'publish', true, $input);
        $post = $this->validatedPost($item, $capability, $input);
        $requestHash = $this->requestHash([
            'distribution_item_id' => $item->getKey(),
            'location_name' => $post['location_name'],
            'provider_payload' => $post['provider_payload'],
            'profile_version' => $capability['profile_version'],
        ]);

        if ($cached = $this->cachedOperation(
            $item,
            'publish',
            $idempotencyKey,
            $requestHash
        )) {
            return $cached;
        }

        $response = $this->google->createLocalPost(
            $account,
            $post['location_name'],
            $post['provider_payload']
        );
        $this->assertSuccessful($response, 'Google Business Profile local post publication failed.');
        $localPostName = trim((string) $response->json('name', ''));

        if ($localPostName === '') {
            throw new RuntimeException('Google Business Profile did not return a local post name.');
        }

        $result = [
            'status' => 'published',
            'local_post_name' => $localPostName,
            'location_name' => $post['location_name'],
            'search_url' => $response->json('searchUrl'),
            'topic_type' => $post['provider_payload']['topicType'],
            'published_at' => now()->toIso8601String(),
            'request_hash' => $requestHash,
            'rate_limit' => $this->google->rateLimit($response),
            ...$this->profileReceipt($capability),
        ];

        $this->persistOperation(
            $item,
            'publish',
            $idempotencyKey,
            $requestHash,
            $post,
            $result
        );
        $item->update(['status' => 'published']);
        $this->audit($user, $account, 'google_business_profile_post_published', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'result' => $result,
        ]);

        return $result;
    }

    public function uploadPhoto(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        array $input,
        string $idempotencyKey
    ): array {
        $capability = $this->assertContext($user, $item, $account, 'photos', true, $input);
        $locationName = $this->requiredString($input, 'location_name', 255);
        $sourceUrl = $this->httpsUrl($this->requiredString($input, 'source_url', 2048), 'photo');
        $category = strtoupper(trim((string) ($input['category'] ?? 'ADDITIONAL')));
        $allowedCategories = [
            'ADDITIONAL',
            'COVER',
            'PROFILE',
            'EXTERIOR',
            'INTERIOR',
            'PRODUCT',
            'AT_WORK',
            'FOOD_AND_DRINK',
            'MENU',
            'COMMON_AREA',
            'ROOMS',
            'TEAMS',
        ];

        if (! in_array($category, $allowedCategories, true)) {
            throw new InvalidArgumentException('The Google Business Profile photo category is invalid.');
        }

        $providerPayload = [
            'mediaFormat' => 'PHOTO',
            'locationAssociation' => ['category' => $category],
            'sourceUrl' => $sourceUrl,
        ];
        $requestHash = $this->requestHash([
            'distribution_item_id' => $item->getKey(),
            'location_name' => $locationName,
            'provider_payload' => $providerPayload,
            'profile_version' => $capability['profile_version'],
        ]);

        if ($cached = $this->cachedOperation(
            $item,
            'photo',
            $idempotencyKey,
            $requestHash
        )) {
            return $cached;
        }

        $response = $this->google->createMedia($account, $locationName, $providerPayload);
        $this->assertSuccessful($response, 'Google Business Profile photo upload failed.');
        $result = [
            'status' => 'uploaded',
            'media_name' => $response->json('name'),
            'google_url' => $response->json('googleUrl'),
            'location_name' => $locationName,
            'category' => $category,
            'uploaded_at' => now()->toIso8601String(),
            'request_hash' => $requestHash,
            'rate_limit' => $this->google->rateLimit($response),
            ...$this->profileReceipt($capability),
        ];

        $this->persistOperation(
            $item,
            'photo',
            $idempotencyKey,
            $requestHash,
            ['location_name' => $locationName, 'provider_payload' => $providerPayload],
            $result
        );
        $this->audit($user, $account, 'google_business_profile_photo_uploaded', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'result' => $result,
        ]);

        return $result;
    }

    public function reconcile(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account
    ): array {
        $this->assertContext($user, $item, $account, 'status_reconciliation', false, []);
        $localPostName = trim((string) data_get(
            $item->payload,
            'google_business_profile.local_post_name',
            ''
        ));

        if ($localPostName === '') {
            throw new InvalidArgumentException('Publish a Google Business Profile local post before reconciliation.');
        }

        $response = $this->google->getLocalPost($account, $localPostName);
        $this->assertSuccessful($response, 'Google Business Profile reconciliation failed.');
        $result = [
            'local_post_name' => $localPostName,
            'state' => $response->json('state', 'UNKNOWN'),
            'topic_type' => $response->json('topicType'),
            'search_url' => $response->json('searchUrl'),
            'update_time' => $response->json('updateTime'),
            'last_reconciled_at' => now()->toIso8601String(),
            'rate_limit' => $this->google->rateLimit($response),
        ];
        $payload = (array) $item->payload;
        data_set($payload, 'google_business_profile.reconciliation', $result);
        $item->update(['payload' => $payload]);
        $this->audit($user, $account, 'google_business_profile_post_reconciled', [
            'distribution_item_id' => $item->getKey(),
            'result' => $result,
        ]);

        return $result;
    }

    public function reviews(
        User $user,
        SocialMediaPlatform $account,
        string $locationName,
        ?string $pageToken = null
    ): array {
        $this->assertProviderOperation($user, $account, 'reviews');
        $response = $this->google->reviews($account, $locationName, $pageToken);
        $this->assertSuccessful($response, 'Google Business Profile reviews could not be read.');
        $result = [
            'reviews' => array_slice(
                array_values((array) $response->json('reviews', [])),
                0,
                100
            ),
            'average_rating' => $response->json('averageRating'),
            'total_review_count' => $response->json('totalReviewCount'),
            'next_page_token' => $response->json('nextPageToken'),
            'rate_limit' => $this->google->rateLimit($response),
        ];
        $this->audit($user, $account, 'google_business_profile_reviews_read', [
            'location_name' => $locationName,
            'review_count' => count($result['reviews']),
            'rate_limit' => $result['rate_limit'],
        ]);

        return $result;
    }

    public function replyToReview(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        string $reviewName,
        string $comment,
        bool $replyApproved,
        string $idempotencyKey
    ): array {
        $capability = $this->assertContext($user, $item, $account, 'review_reply', true, []);

        if (! $replyApproved) {
            throw new RuntimeException('Explicit approval is required before replying to a review.');
        }

        $comment = trim($comment);

        if ($comment === '' || mb_strlen($comment) > 4096) {
            throw new InvalidArgumentException('The Google Business Profile review reply is invalid.');
        }

        $requestHash = $this->requestHash([
            'distribution_item_id' => $item->getKey(),
            'review_name' => $reviewName,
            'comment' => $comment,
            'profile_version' => $capability['profile_version'],
        ]);

        if ($cached = $this->cachedOperation(
            $item,
            'review_reply',
            $idempotencyKey,
            $requestHash
        )) {
            return $cached;
        }

        $response = $this->google->replyToReview($account, $reviewName, $comment);
        $this->assertSuccessful($response, 'Google Business Profile review reply failed.');
        $result = [
            'status' => 'replied',
            'review_name' => $reviewName,
            'comment_hash' => hash('sha256', $comment),
            'updated_at' => $response->json('updateTime', now()->toIso8601String()),
            'request_hash' => $requestHash,
            'rate_limit' => $this->google->rateLimit($response),
            ...$this->profileReceipt($capability),
        ];

        $this->persistOperation(
            $item,
            'review_reply',
            $idempotencyKey,
            $requestHash,
            ['review_name' => $reviewName, 'comment_hash' => hash('sha256', $comment)],
            $result
        );
        $this->audit($user, $account, 'google_business_profile_review_replied', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'result' => $result,
        ]);

        return $result;
    }

    public function reviewHandoff(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        array $review
    ): array {
        $capability = $this->assertContext($user, $item, $account, 'reviews', false, []);
        $reviewName = $this->requiredString($review, 'review_name', 500);
        $comment = $this->requiredString($review, 'comment', 10000);
        $handoff = [
            'review_name' => $reviewName,
            'reviewer_name' => trim((string) ($review['reviewer_name'] ?? '')),
            'star_rating' => trim((string) ($review['star_rating'] ?? '')),
            'comment' => $comment,
            'received_at' => (string) ($review['received_at'] ?? now()->toIso8601String()),
            'status' => 'human_handoff_required',
            'handoff_targets' => (array) data_get(
                $capability,
                'handoff_targets.review',
                data_get($capability, 'handoff_targets.enquiry', [])
            ),
            'automated_reply_sent' => false,
        ];
        $payload = (array) $item->payload;
        $handoffs = array_values((array) data_get(
            $payload,
            'google_business_profile.review_handoffs',
            []
        ));
        $existing = collect($handoffs)->firstWhere('review_name', $reviewName);

        if (is_array($existing)) {
            return $existing;
        }

        $handoffs[] = $handoff;
        data_set(
            $payload,
            'google_business_profile.review_handoffs',
            array_slice($handoffs, -100)
        );
        $item->update(['payload' => $payload]);
        $this->audit($user, $account, 'google_business_profile_review_handoff', [
            'distribution_item_id' => $item->getKey(),
            'handoff' => $handoff,
        ]);

        return $handoff;
    }

    public function performance(
        User $user,
        SocialMediaPlatform $account,
        string $locationName,
        string $startDate,
        string $endDate,
        array $metrics
    ): array {
        $this->assertProviderOperation($user, $account, 'analytics');
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endDate)->startOfDay();

        if ($end->lt($start) || $start->diffInDays($end) > 366) {
            throw new InvalidArgumentException('The Google Business Profile performance date range is invalid.');
        }

        $allowed = [
            'BUSINESS_IMPRESSIONS_DESKTOP_MAPS',
            'BUSINESS_IMPRESSIONS_DESKTOP_SEARCH',
            'BUSINESS_IMPRESSIONS_MOBILE_MAPS',
            'BUSINESS_IMPRESSIONS_MOBILE_SEARCH',
            'BUSINESS_CONVERSATIONS',
            'BUSINESS_DIRECTION_REQUESTS',
            'CALL_CLICKS',
            'WEBSITE_CLICKS',
            'BUSINESS_BOOKINGS',
            'BUSINESS_FOOD_ORDERS',
            'BUSINESS_FOOD_MENU_CLICKS',
        ];
        $metrics = array_values(array_unique(array_intersect($allowed, $metrics)));

        if ($metrics === []) {
            throw new InvalidArgumentException('At least one supported performance metric is required.');
        }

        $response = $this->google->performance(
            $account,
            $locationName,
            $metrics,
            $start->toDateString(),
            $end->toDateString()
        );
        $this->assertSuccessful($response, 'Google Business Profile performance metrics failed.');
        $result = [
            'location_name' => $locationName,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'metrics' => $metrics,
            'time_series' => (array) $response->json('multiDailyMetricTimeSeries', []),
            'rate_limit' => $this->google->rateLimit($response),
        ];
        $this->audit($user, $account, 'google_business_profile_performance_read', [
            'location_name' => $locationName,
            'start_date' => $result['start_date'],
            'end_date' => $result['end_date'],
            'metrics' => $metrics,
            'rate_limit' => $result['rate_limit'],
        ]);

        return $result;
    }

    private function assertContext(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        string $operation,
        bool $requiresApproval,
        array $input
    ): array {
        if ((int) $item->user_id !== (int) $user->getKey()) {
            throw new RuntimeException('The Google Business Profile item is outside the current tenant.');
        }

        $this->assertProviderOperation($user, $account, $operation);
        $capability = $this->capabilities->forVerticalDestination(
            $user,
            self::DESTINATION,
            (string) $item->content_type,
            $this->verticalFrom($item, $input),
            $this->subtypeFrom($item, $input),
            $account
        );

        if (! $capability['available']) {
            throw new RuntimeException(
                'Google Business Profile is unavailable for this vertical/content combination: '
                . ($capability['reason'] ?? 'unknown')
            );
        }

        if ($requiresApproval && $item->approval_status !== 'approved') {
            throw new RuntimeException(
                'The Google Business Profile operation requires approval before external changes.'
            );
        }

        return $capability;
    }

    private function assertProviderOperation(
        User $user,
        SocialMediaPlatform $account,
        string $operation
    ): void {
        $this->assertAccount($user, $account);
        $base = $this->capabilities->forDestination($user, self::DESTINATION, $account);

        if (! $base['available']) {
            throw new RuntimeException(
                'Google Business Profile is unavailable for this account: '
                . ($base['reason'] ?? 'unknown')
            );
        }

        if (! (bool) data_get($account->credentials, "provider_capabilities.{$operation}", false)) {
            throw new RuntimeException(
                "The connected Google Business Profile account does not grant {$operation}."
            );
        }
    }

    private function assertAccount(User $user, SocialMediaPlatform $account): void
    {
        if ((int) $account->user_id !== (int) $user->getKey()
            || (string) $account->platform !== PlatformEnum::google_business_profile->value
            || ! $account->isConnected()) {
            throw new RuntimeException('A connected Google Business Profile account is required.');
        }
    }

    private function validatedPost(
        DistributionItem $item,
        array $capability,
        array $input
    ): array {
        $locationName = $this->requiredString($input, 'location_name', 255);
        $summary = trim((string) ($input['content'] ?? $item->resolvedContent() ?? ''));

        if ($summary === '' || mb_strlen($summary) > 1500) {
            throw new InvalidArgumentException(
                'Google Business Profile local post content must be between 1 and 1500 characters.'
            );
        }

        $topicType = strtoupper(trim((string) ($input['topic_type'] ?? 'STANDARD')));

        if (! in_array($topicType, ['STANDARD', 'OFFER', 'EVENT'], true)) {
            throw new InvalidArgumentException('The Google Business Profile topic type is invalid.');
        }

        $providerPayload = [
            'languageCode' => (string) ($input['language_code'] ?? 'en-AU'),
            'summary' => $summary,
            'topicType' => $topicType,
        ];
        $callToAction = (array) ($input['call_to_action'] ?? []);

        if ($callToAction !== []) {
            $actionType = strtoupper(trim((string) ($callToAction['action_type'] ?? '')));
            $url = $this->httpsUrl(
                $this->requiredString($callToAction, 'url', 2048),
                'call-to-action'
            );
            $allowedActions = [
                'BOOK',
                'ORDER',
                'SHOP',
                'LEARN_MORE',
                'SIGN_UP',
                'CALL',
            ];

            if (! in_array($actionType, $allowedActions, true)) {
                throw new InvalidArgumentException(
                    'The Google Business Profile call-to-action type is invalid.'
                );
            }

            $providerPayload['callToAction'] = [
                'actionType' => $actionType,
                'url' => $url,
            ];
        }

        $media = [];

        foreach (array_slice((array) ($input['media'] ?? []), 0, 10) as $mediaItem) {
            $sourceUrl = is_array($mediaItem)
                ? (string) ($mediaItem['source_url'] ?? '')
                : (string) $mediaItem;

            if ($sourceUrl !== '') {
                $media[] = [
                    'mediaFormat' => 'PHOTO',
                    'sourceUrl' => $this->httpsUrl($sourceUrl, 'media'),
                ];
            }
        }

        if ($media !== []) {
            $providerPayload['media'] = $media;
        }

        if ($topicType === 'OFFER') {
            $offer = (array) ($input['offer'] ?? []);
            $couponCode = trim((string) ($offer['coupon_code'] ?? ''));
            $redeemOnlineUrl = trim((string) ($offer['redeem_online_url'] ?? ''));
            $terms = trim((string) ($offer['terms_conditions'] ?? ''));
            $providerPayload['offer'] = array_filter([
                'couponCode' => $couponCode !== '' ? $couponCode : null,
                'redeemOnlineUrl' => $redeemOnlineUrl !== ''
                    ? $this->httpsUrl($redeemOnlineUrl, 'offer redemption')
                    : null,
                'termsConditions' => $terms !== '' ? $terms : null,
            ], static fn ($value) => $value !== null);
            $providerPayload['event'] = $this->validatedEvent(
                (array) ($input['event'] ?? []),
                true
            );
        } elseif ($topicType === 'EVENT') {
            $providerPayload['event'] = $this->validatedEvent(
                (array) ($input['event'] ?? []),
                true
            );
        }

        $aliases = [
            'content' => $summary,
            'description' => $summary,
            'title' => trim((string) ($item->title ?? $input['title'] ?? '')),
            'media' => $media,
            'images' => $media,
            'location' => $locationName,
            'call_to_action' => $providerPayload['callToAction'] ?? null,
        ];
        $verticalFields = (array) ($input['vertical_fields'] ?? []);

        foreach ((array) ($capability['required_fields'] ?? []) as $field) {
            $value = $aliases[$field] ?? data_get($verticalFields, $field);

            if ($value === null || $value === '' || $value === []) {
                throw new InvalidArgumentException(
                    "Missing required Google Business Profile vertical field: {$field}."
                );
            }
        }

        return [
            'location_name' => $locationName,
            'provider_payload' => $providerPayload,
            'vertical_fields' => $verticalFields,
        ];
    }

    private function validatedEvent(array $event, bool $required): array
    {
        $title = trim((string) ($event['title'] ?? ''));
        $startDate = trim((string) data_get($event, 'start_date', ''));
        $endDate = trim((string) data_get($event, 'end_date', ''));

        if ($required && ($title === '' || $startDate === '')) {
            throw new InvalidArgumentException(
                'Google Business Profile offers and events require an event title and start date.'
            );
        }

        $payload = ['title' => $title];
        $payload['schedule'] = [
            'startDate' => $this->googleDate($startDate),
        ];

        if ($endDate !== '') {
            $payload['schedule']['endDate'] = $this->googleDate($endDate);
        }

        foreach (['start_time' => 'startTime', 'end_time' => 'endTime'] as $inputKey => $outputKey) {
            $value = trim((string) ($event[$inputKey] ?? ''));

            if ($value !== '') {
                $payload['schedule'][$outputKey] = $this->googleTime($value);
            }
        }

        return $payload;
    }

    private function googleDate(string $date): array
    {
        $parsed = Carbon::createFromFormat('Y-m-d', $date);

        return [
            'year' => $parsed->year,
            'month' => $parsed->month,
            'day' => $parsed->day,
        ];
    }

    private function googleTime(string $time): array
    {
        $parsed = Carbon::createFromFormat('H:i', $time);

        return [
            'hours' => $parsed->hour,
            'minutes' => $parsed->minute,
        ];
    }

    private function requiredString(array $input, string $key, int $max): string
    {
        $value = trim((string) data_get($input, $key, ''));

        if ($value === '' || mb_strlen($value) > $max) {
            throw new InvalidArgumentException("The {$key} field is required and must be valid.");
        }

        return $value;
    }

    private function httpsUrl(string $url, string $label): string
    {
        $url = trim($url);

        if (! filter_var($url, FILTER_VALIDATE_URL)
            || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            throw new InvalidArgumentException("The Google Business Profile {$label} URL must use HTTPS.");
        }

        return $url;
    }

    private function cachedOperation(
        DistributionItem $item,
        string $operation,
        string $idempotencyKey,
        string $requestHash
    ): ?array {
        $stored = data_get(
            $item->payload,
            'google_business_profile.operations.' . $operation . '.'
                . $this->idempotencySlot($idempotencyKey)
        );

        if (! is_array($stored) || ($stored['status'] ?? null) !== 'succeeded') {
            return null;
        }

        if (! hash_equals((string) ($stored['idempotency_key'] ?? ''), $idempotencyKey)
            || ! hash_equals((string) ($stored['request_hash'] ?? ''), $requestHash)) {
            throw new RuntimeException(
                'idempotency_key_conflict: the key was already used for different input.'
            );
        }

        return (array) ($stored['result'] ?? []);
    }

    private function persistOperation(
        DistributionItem $item,
        string $operation,
        string $idempotencyKey,
        string $requestHash,
        array $snapshot,
        array $result
    ): void {
        $payload = (array) $item->payload;
        $operations = (array) data_get(
            $payload,
            "google_business_profile.operations.{$operation}",
            []
        );
        $operations[$this->idempotencySlot($idempotencyKey)] = [
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'status' => 'succeeded',
            'snapshot' => $snapshot,
            'result' => $result,
            'completed_at' => now()->toIso8601String(),
        ];
        $operations = array_slice(
            $operations,
            -self::IDEMPOTENCY_HISTORY_LIMIT,
            null,
            true
        );
        data_set($payload, "google_business_profile.operations.{$operation}", $operations);

        if (isset($result['local_post_name'])) {
            data_set(
                $payload,
                'google_business_profile.local_post_name',
                $result['local_post_name']
            );
        }

        data_set($payload, 'google_business_profile.last_receipt', $result);
        $item->update(['payload' => $payload]);
        $item->refresh();
    }

    private function requestHash(array $payload): string
    {
        return hash(
            'sha256',
            json_encode($this->canonicalise($payload), JSON_THROW_ON_ERROR)
        );
    }

    private function canonicalise(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->canonicalise($item), $value);
        }

        ksort($value);

        return array_map(fn ($item) => $this->canonicalise($item), $value);
    }

    private function idempotencySlot(string $key): string
    {
        $key = trim($key);

        if ($key === '' || mb_strlen($key) > 128) {
            throw new InvalidArgumentException('A valid idempotency key is required.');
        }

        return hash('sha256', $key);
    }

    private function profileReceipt(array $capability): array
    {
        return [
            'vertical' => $capability['vertical'],
            'business_subtype' => $capability['business_subtype'],
            'suitability' => $capability['suitability'],
            'profile_version' => $capability['profile_version'],
            'profile_provenance' => $capability['profile_provenance'],
            'vertical_context_id' => $capability['vertical_context_id'] ?? null,
            'vertical_context_hash' => $capability['vertical_context_hash'] ?? null,
        ];
    }

    private function verticalFrom(DistributionItem $item, array $input): ?string
    {
        return $this->stringOrNull(data_get($item->payload, 'vertical'))
            ?? $this->stringOrNull(data_get($item->payload, 'vertical_context.vertical'))
            ?? $this->stringOrNull(data_get($input, 'vertical'));
    }

    private function subtypeFrom(DistributionItem $item, array $input): ?string
    {
        return $this->stringOrNull(data_get($item->payload, 'business_subtype'))
            ?? $this->stringOrNull(data_get($item->payload, 'vertical_context.business_subtype'))
            ?? $this->stringOrNull(data_get($input, 'business_subtype'))
            ?? $this->stringOrNull(data_get($input, 'subtype'));
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function locationSummary(array $location): array
    {
        return [
            'name' => $location['name'] ?? null,
            'title' => $location['title'] ?? null,
            'store_code' => $location['storeCode'] ?? null,
            'website_uri' => $location['websiteUri'] ?? null,
            'primary_category' => data_get($location, 'categories.primaryCategory.displayName'),
            'place_id' => data_get($location, 'metadata.placeId'),
            'maps_uri' => data_get($location, 'metadata.mapsUri'),
            'can_operate_local_post' => data_get($location, 'metadata.canOperateLocalPost'),
        ];
    }

    private function assertSuccessful(Response $response, string $message): void
    {
        if ($response->successful()) {
            return;
        }

        $errorCode = (string) ($response->json('error.status')
            ?? $response->json('error.code')
            ?? $response->status());
        $errorMessage = (string) ($response->json('error.message') ?? $message);

        throw new RuntimeException("{$message} [{$errorCode}] {$errorMessage}");
    }

    private function audit(
        User $user,
        SocialMediaPlatform $account,
        string $action,
        array $snapshot
    ): void {
        if (! Schema::hasTable('ext_social_media_distribution_audits')) {
            return;
        }

        DB::table('ext_social_media_distribution_audits')->insert([
            'user_id' => $user->getKey(),
            'social_media_platform_id' => $account->getKey(),
            'destination' => self::DESTINATION,
            'action' => $action,
            'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
