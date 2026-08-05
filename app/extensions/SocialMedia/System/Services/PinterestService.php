<?php

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\Pinterest;
use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class PinterestService
{
    private const DESTINATION = 'pinterest';

    private const IDEMPOTENCY_HISTORY_LIMIT = 10;

    public function __construct(
        private readonly Pinterest $pinterest,
        private readonly DistributionCapabilityService $capabilities
    ) {}

    public function readiness(User $user, SocialMediaPlatform $account): array
    {
        $this->assertAccount($user, $account);
        $userResponse = $this->pinterest->userAccount($account);
        $boardsResponse = $this->pinterest->boards($account);
        $this->assertSuccessful($userResponse, 'Pinterest user discovery failed.');
        $boards = $boardsResponse->successful()
            ? array_slice(array_values((array) $boardsResponse->json('items', [])), 0, 100)
            : [];
        $credentials = (array) $account->credentials;
        $scopes = (array) ($credentials['authorized_scopes'] ?? []);
        $scope = static fn (string $required): bool => in_array($required, $scopes, true);
        $providerCapabilities = [
            'account_discovery' => $scope('user_accounts:read'),
            'boards' => $scope('boards:read') && $boardsResponse->successful(),
            'board_write' => $scope('boards:write'),
            'publish' => $scope('pins:write') && $boards !== [],
            'analytics' => $scope('pins:read'),
            'status_reconciliation' => $scope('pins:read'),
            'product_links' => $scope('pins:write'),
        ];
        $result = [
            'ready' => $providerCapabilities['publish'],
            'user_account' => (array) $userResponse->json(),
            'boards' => $this->boardSummaries($boards),
            'provider_capabilities' => $providerCapabilities,
            'rate_limit' => [
                'user_account' => $this->pinterest->rateLimit($userResponse),
                'boards' => $this->pinterest->rateLimit($boardsResponse),
            ],
            'checked_at' => now()->toIso8601String(),
        ];

        $credentials['boards'] = $result['boards'];
        $credentials['provider_capabilities'] = $providerCapabilities;
        $credentials['health'] = [
            'status' => $result['ready'] ? 'healthy' : 'limited',
            'checked_at' => $result['checked_at'],
            'boards_count' => count($boards),
            'rate_limit' => $result['rate_limit'],
        ];
        $account->update(['credentials' => $credentials]);
        $this->audit($user, $account, 'pinterest_readiness', $result);

        return $result;
    }

    public function boards(
        User $user,
        SocialMediaPlatform $account,
        ?string $bookmark = null
    ): array {
        $this->assertProviderOperation($user, $account, 'boards');
        $response = $this->pinterest->boards($account, $bookmark);
        $this->assertSuccessful($response, 'Pinterest boards could not be read.');
        $result = [
            'boards' => $this->boardSummaries(array_slice(
                array_values((array) $response->json('items', [])),
                0,
                100
            )),
            'bookmark' => $response->json('bookmark'),
            'rate_limit' => $this->pinterest->rateLimit($response),
        ];
        $this->audit($user, $account, 'pinterest_boards_read', [
            'board_count' => count($result['boards']),
            'rate_limit' => $result['rate_limit'],
        ]);

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
        $pin = $this->validatedPin($item, $account, $capability, $input);
        $requestHash = $this->requestHash([
            'distribution_item_id' => $item->getKey(),
            'provider_payload' => $pin['provider_payload'],
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

        $response = $this->pinterest->createPin($account, $pin['provider_payload']);
        $this->assertSuccessful($response, 'Pinterest Pin publication failed.');
        $pinId = trim((string) $response->json('id', ''));

        if ($pinId === '') {
            throw new RuntimeException('Pinterest did not return a Pin ID.');
        }

        $result = [
            'status' => 'published',
            'pin_id' => $pinId,
            'board_id' => $pin['provider_payload']['board_id'],
            'link' => $pin['provider_payload']['link'] ?? null,
            'published_at' => now()->toIso8601String(),
            'request_hash' => $requestHash,
            'rate_limit' => $this->pinterest->rateLimit($response),
            ...$this->profileReceipt($capability),
        ];

        $this->persistOperation(
            $item,
            'publish',
            $idempotencyKey,
            $requestHash,
            $pin,
            $result
        );
        $item->update(['status' => 'published']);
        $this->audit($user, $account, 'pinterest_pin_published', [
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
        $pinId = trim((string) data_get($item->payload, 'pinterest.pin_id', ''));

        if ($pinId === '') {
            throw new InvalidArgumentException('Publish a Pinterest Pin before reconciliation.');
        }

        $response = $this->pinterest->getPin($account, $pinId);
        $this->assertSuccessful($response, 'Pinterest Pin reconciliation failed.');
        $result = [
            'pin_id' => $pinId,
            'board_id' => $response->json('board_id'),
            'title' => $response->json('title'),
            'link' => $response->json('link'),
            'media' => $response->json('media'),
            'is_standard' => $response->json('is_standard'),
            'last_reconciled_at' => now()->toIso8601String(),
            'rate_limit' => $this->pinterest->rateLimit($response),
        ];
        $payload = (array) $item->payload;
        data_set($payload, 'pinterest.reconciliation', $result);
        $item->update(['payload' => $payload]);
        $this->audit($user, $account, 'pinterest_pin_reconciled', [
            'distribution_item_id' => $item->getKey(),
            'result' => $result,
        ]);

        return $result;
    }

    public function analytics(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        string $startDate,
        string $endDate,
        array $metrics
    ): array {
        $this->assertContext($user, $item, $account, 'analytics', false, []);
        $pinId = trim((string) data_get($item->payload, 'pinterest.pin_id', ''));

        if ($pinId === '') {
            throw new InvalidArgumentException('Publish a Pinterest Pin before reading analytics.');
        }

        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endDate)->startOfDay();

        if ($end->lt($start) || $start->diffInDays($end) > 90) {
            throw new InvalidArgumentException('The Pinterest analytics date range is invalid.');
        }

        $allowed = [
            'IMPRESSION',
            'SAVE',
            'PIN_CLICK',
            'OUTBOUND_CLICK',
            'VIDEO_MRC_VIEW',
            'VIDEO_AVG_WATCH_TIME',
            'VIDEO_V50_WATCH_TIME',
            'QUARTILE_95_PERCENT_VIEW',
        ];
        $metrics = array_values(array_unique(array_intersect($allowed, $metrics)));

        if ($metrics === []) {
            throw new InvalidArgumentException('At least one supported Pinterest metric is required.');
        }

        $response = $this->pinterest->pinAnalytics(
            $account,
            $pinId,
            $start->toDateString(),
            $end->toDateString(),
            $metrics
        );
        $this->assertSuccessful($response, 'Pinterest analytics failed.');
        $result = [
            'pin_id' => $pinId,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'metrics' => $metrics,
            'data' => (array) $response->json(),
            'rate_limit' => $this->pinterest->rateLimit($response),
        ];
        $this->audit($user, $account, 'pinterest_pin_analytics_read', [
            'distribution_item_id' => $item->getKey(),
            'pin_id' => $pinId,
            'start_date' => $result['start_date'],
            'end_date' => $result['end_date'],
            'metrics' => $metrics,
            'rate_limit' => $result['rate_limit'],
        ]);

        return $result;
    }

    public function engagementHandoff(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        array $engagement
    ): array {
        $capability = $this->assertContext(
            $user,
            $item,
            $account,
            'status_reconciliation',
            false,
            []
        );
        $engagementId = $this->requiredString($engagement, 'engagement_id', 255);
        $message = $this->requiredString($engagement, 'message', 10000);
        $handoff = [
            'engagement_id' => $engagementId,
            'actor_alias' => trim((string) ($engagement['actor_alias'] ?? '')),
            'message' => $message,
            'received_at' => (string) ($engagement['received_at'] ?? now()->toIso8601String()),
            'status' => 'human_handoff_required',
            'handoff_targets' => (array) data_get(
                $capability,
                'handoff_targets.enquiry',
                []
            ),
            'automated_reply_sent' => false,
        ];
        $payload = (array) $item->payload;
        $handoffs = array_values((array) data_get(
            $payload,
            'pinterest.engagement_handoffs',
            []
        ));
        $existing = collect($handoffs)->firstWhere('engagement_id', $engagementId);

        if (is_array($existing)) {
            return $existing;
        }

        $handoffs[] = $handoff;
        data_set(
            $payload,
            'pinterest.engagement_handoffs',
            array_slice($handoffs, -100)
        );
        $item->update(['payload' => $payload]);
        $this->audit($user, $account, 'pinterest_engagement_handoff', [
            'distribution_item_id' => $item->getKey(),
            'handoff' => $handoff,
        ]);

        return $handoff;
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
            throw new RuntimeException('The Pinterest item is outside the current tenant.');
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
                'Pinterest is unavailable for this vertical/content combination: '
                . ($capability['reason'] ?? 'unknown')
            );
        }

        if ($requiresApproval && $item->approval_status !== 'approved') {
            throw new RuntimeException(
                'The Pinterest operation requires approval before external changes.'
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
                'Pinterest is unavailable for this account: ' . ($base['reason'] ?? 'unknown')
            );
        }

        if (! (bool) data_get($account->credentials, "provider_capabilities.{$operation}", false)) {
            throw new RuntimeException(
                "The connected Pinterest account does not grant {$operation}."
            );
        }
    }

    private function assertAccount(User $user, SocialMediaPlatform $account): void
    {
        if ((int) $account->user_id !== (int) $user->getKey()
            || (string) $account->platform !== PlatformEnum::pinterest->value
            || ! $account->isConnected()) {
            throw new RuntimeException('A connected Pinterest account is required.');
        }
    }

    private function validatedPin(
        DistributionItem $item,
        SocialMediaPlatform $account,
        array $capability,
        array $input
    ): array {
        $boardId = $this->requiredString($input, 'board_id', 255);
        $knownBoardIds = array_values(array_filter(array_map(
            static fn (array $board): ?string => isset($board['id']) ? (string) $board['id'] : null,
            (array) data_get($account->credentials, 'boards', [])
        )));

        if (! in_array($boardId, $knownBoardIds, true)) {
            throw new InvalidArgumentException('The selected Pinterest board is not available to this account.');
        }

        if (! (bool) ($input['original_media_confirmed'] ?? false)) {
            throw new RuntimeException(
                'Pinterest publication requires confirmation that the image is approved original content.'
            );
        }

        $title = trim((string) ($input['title'] ?? $item->title ?? ''));
        $description = trim((string) ($input['description'] ?? $item->resolvedContent() ?? ''));
        $imageUrl = $this->httpsUrl(
            $this->requiredString($input, 'image_url', 2048),
            'image'
        );

        if ($title === '' || mb_strlen($title) > 100) {
            throw new InvalidArgumentException('Pinterest Pin title must be between 1 and 100 characters.');
        }

        if (mb_strlen($description) > 500) {
            throw new InvalidArgumentException('Pinterest Pin description cannot exceed 500 characters.');
        }

        $link = trim((string) ($input['link'] ?? ''));

        if ($item->content_type === DistributionItem::TYPE_PRODUCT_OFFER && $link === '') {
            throw new InvalidArgumentException('Pinterest product offers require an approved product link.');
        }

        if ($link !== '') {
            $link = $this->httpsUrl($link, 'destination');
        }

        $altText = trim((string) ($input['alt_text'] ?? $title));

        if (mb_strlen($altText) > 500) {
            throw new InvalidArgumentException('Pinterest Pin alt text cannot exceed 500 characters.');
        }

        $providerPayload = array_filter([
            'board_id' => $boardId,
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'alt_text' => $altText,
            'link' => $link !== '' ? $link : null,
            'media_source' => [
                'source_type' => 'image_url',
                'url' => $imageUrl,
            ],
        ], static fn ($value) => $value !== null);
        $aliases = [
            'title' => $title,
            'content' => $description,
            'description' => $description,
            'media' => [$imageUrl],
            'images' => [$imageUrl],
            'destination_url' => $link,
            'call_to_action' => $link,
            'board_id' => $boardId,
        ];
        $verticalFields = (array) ($input['vertical_fields'] ?? []);

        foreach ((array) ($capability['required_fields'] ?? []) as $field) {
            $value = $aliases[$field] ?? data_get($verticalFields, $field);

            if ($value === null || $value === '' || $value === []) {
                throw new InvalidArgumentException(
                    "Missing required Pinterest vertical field: {$field}."
                );
            }
        }

        return [
            'provider_payload' => $providerPayload,
            'vertical_fields' => $verticalFields,
            'original_media_confirmed' => true,
        ];
    }

    private function boardSummaries(array $boards): array
    {
        return array_map(
            static fn (array $board): array => [
                'id' => $board['id'] ?? null,
                'name' => $board['name'] ?? null,
                'description' => $board['description'] ?? null,
                'privacy' => $board['privacy'] ?? null,
                'pin_count' => $board['pin_count'] ?? null,
                'follower_count' => $board['follower_count'] ?? null,
            ],
            $boards
        );
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
            throw new InvalidArgumentException("The Pinterest {$label} URL must use HTTPS.");
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
            'pinterest.operations.' . $operation . '.' . $this->idempotencySlot($idempotencyKey)
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
        $operations = (array) data_get($payload, "pinterest.operations.{$operation}", []);
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
        data_set($payload, "pinterest.operations.{$operation}", $operations);

        if (isset($result['pin_id'])) {
            data_set($payload, 'pinterest.pin_id', $result['pin_id']);
        }

        data_set($payload, 'pinterest.last_receipt', $result);
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

    private function assertSuccessful(Response $response, string $message): void
    {
        if ($response->successful()) {
            return;
        }

        $errorCode = (string) ($response->json('code')
            ?? $response->json('error.code')
            ?? $response->status());
        $errorMessage = (string) ($response->json('message')
            ?? $response->json('error.message')
            ?? $message);

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
