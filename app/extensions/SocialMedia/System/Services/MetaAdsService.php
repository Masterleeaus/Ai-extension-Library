<?php

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\Facebook;
use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Extensions\SocialMedia\System\Models\PaidMediaCampaign;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class MetaAdsService
{
    public function __construct(
        private readonly DistributionCapabilityService $capabilities
    ) {}

    public function saveDraft(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        array $payload,
        ?PaidMediaCampaign $campaign = null
    ): PaidMediaCampaign {
        $this->assertItemAndAccount($user, $item, $account);
        $validated = $this->validatedDraft($account, $payload);
        $fingerprint = $this->approvalFingerprint($validated);

        if ($campaign) {
            $this->assertCampaignContext($user, $campaign, $account);

            if ($campaign->status === 'active') {
                throw new RuntimeException('Pause the Meta campaign before making material changes.');
            }

            if (! hash_equals((string) $campaign->payload_fingerprint, $fingerprint)) {
                $this->appendApprovalDecision(
                    $campaign,
                    $user,
                    'material_change_requires_new_approval',
                    $fingerprint,
                    'Campaign budget, schedule, targeting or creative changed.'
                );
            }

            $campaign->update([
                ...$validated,
                'payload_fingerprint' => $fingerprint,
                'status' => 'draft',
            ]);

            return $campaign->refresh();
        }

        return PaidMediaCampaign::query()->create([
            'user_id' => $user->getKey(),
            'social_media_platform_id' => $account->getKey(),
            'distribution_item_id' => $item->getKey(),
            ...$validated,
            'payload_fingerprint' => $fingerprint,
            'status' => 'draft',
        ]);
    }

    public function recommendAudience(User $user, PaidMediaCampaign $campaign): array
    {
        $this->assertCampaignOwner($user, $campaign);
        $targeting = (array) data_get($campaign->adset_payload, 'targeting', []);
        $countries = array_values((array) data_get($targeting, 'geo_locations.countries', []));
        $ageMin = max(18, (int) data_get($targeting, 'age_min', 18));
        $ageMax = min(65, (int) data_get($targeting, 'age_max', 65));

        return [
            'recommendation_only' => true,
            'objective' => $campaign->objective,
            'suggested_targeting' => [
                'geo_locations' => [
                    'countries' => $countries ?: ['AU'],
                ],
                'age_min' => min($ageMin, $ageMax),
                'age_max' => max($ageMin, $ageMax),
                'advantage_audience' => true,
            ],
            'placement_guidance' => [
                'automatic_placements' => true,
                'manual_placements_require_review' => true,
            ],
            'spend_authority_granted' => false,
            'activation_authority_granted' => false,
        ];
    }

    public function approveBudget(
        User $actor,
        PaidMediaCampaign $campaign,
        bool $humanConfirmed,
        string $confirmation
    ): array {
        $this->assertCampaignOwner($actor, $campaign);
        $this->assertPermission($actor, $campaign, 'approval_permission');

        if (! $humanConfirmed || ! hash_equals($campaign->payload_fingerprint, $confirmation)) {
            throw new RuntimeException('Explicit human budget approval is required for the current campaign fingerprint.');
        }

        $approvalId = DB::table('ext_social_media_paid_media_approvals')->insertGetId([
            'paid_media_campaign_id' => $campaign->getKey(),
            'user_id' => $campaign->user_id,
            'actor_id' => $actor->getKey(),
            'decision' => 'approved',
            'payload_fingerprint' => $campaign->payload_fingerprint,
            'budget_minor' => $campaign->budget_minor,
            'spend_cap_minor' => $campaign->spend_cap_minor,
            'currency' => $campaign->currency,
            'starts_at' => $campaign->starts_at,
            'ends_at' => $campaign->ends_at,
            'supersedes_id' => $this->latestApproval($campaign)?->id,
            'reason' => 'Explicit human budget approval.',
            'created_at' => now(),
        ]);

        $campaign->update(['status' => 'approved']);

        return [
            'approval_id' => $approvalId,
            'decision' => 'approved',
            'payload_fingerprint' => $campaign->payload_fingerprint,
            'budget_minor' => $campaign->budget_minor,
            'spend_cap_minor' => $campaign->spend_cap_minor,
            'currency' => $campaign->currency,
        ];
    }

    public function syncPaused(
        User $user,
        PaidMediaCampaign $campaign,
        string $idempotencyKey
    ): array {
        $account = $this->accountForCampaign($user, $campaign);
        $this->assertCurrentApproval($campaign);

        if ($cached = $this->receiptResult($campaign, 'sync_paused', $idempotencyKey)) {
            return $cached;
        }

        $facebook = $this->facebookFor($account);
        $campaignPayload = [
            ...$campaign->campaign_payload,
            'name' => $campaign->name,
            'objective' => $campaign->objective,
            'status' => 'PAUSED',
            'special_ad_categories' => json_encode(
                array_values((array) data_get($campaign->campaign_payload, 'special_ad_categories', [])),
                JSON_THROW_ON_ERROR
            ),
        ];
        $campaignResponse = $facebook->createCampaign($campaign->ad_account_id, $campaignPayload);
        $metaCampaignId = $this->providerId($campaignResponse, 'Meta campaign creation failed.');

        $adSetPayload = [
            ...Arr::except($campaign->adset_payload, ['targeting', 'promoted_object']),
            'name' => (string) data_get($campaign->adset_payload, 'name', $campaign->name . ' Ad Set'),
            'campaign_id' => $metaCampaignId,
            'status' => 'PAUSED',
            'start_time' => $campaign->starts_at?->toIso8601String(),
            'end_time' => $campaign->ends_at?->toIso8601String(),
            'targeting' => json_encode((array) data_get($campaign->adset_payload, 'targeting', []), JSON_THROW_ON_ERROR),
        ];

        if (data_get($campaign->adset_payload, 'promoted_object')) {
            $adSetPayload['promoted_object'] = json_encode(
                (array) data_get($campaign->adset_payload, 'promoted_object'),
                JSON_THROW_ON_ERROR
            );
        }

        $budgetField = $campaign->budget_type === 'lifetime' ? 'lifetime_budget' : 'daily_budget';
        $adSetPayload[$budgetField] = $campaign->budget_minor;
        $adSetResponse = $facebook->createAdSet($campaign->ad_account_id, array_filter(
            $adSetPayload,
            static fn ($value) => $value !== null && $value !== ''
        ));
        $metaAdSetId = $this->providerId($adSetResponse, 'Meta ad set creation failed.');

        $creativePayload = [
            ...Arr::except($campaign->creative_payload, ['object_story_spec', 'asset_feed_spec']),
            'name' => (string) data_get($campaign->creative_payload, 'name', $campaign->name . ' Creative'),
            'object_story_spec' => json_encode(
                (array) data_get($campaign->creative_payload, 'object_story_spec', []),
                JSON_THROW_ON_ERROR
            ),
        ];

        if (data_get($campaign->creative_payload, 'asset_feed_spec')) {
            $creativePayload['asset_feed_spec'] = json_encode(
                (array) data_get($campaign->creative_payload, 'asset_feed_spec'),
                JSON_THROW_ON_ERROR
            );
        }

        $creativeResponse = $facebook->createAdCreative($campaign->ad_account_id, $creativePayload);
        $metaCreativeId = $this->providerId($creativeResponse, 'Meta creative creation failed.');

        $adPayload = [
            ...Arr::except($campaign->ad_payload, ['creative']),
            'name' => (string) data_get($campaign->ad_payload, 'name', $campaign->name . ' Ad'),
            'adset_id' => $metaAdSetId,
            'creative' => json_encode(['creative_id' => $metaCreativeId], JSON_THROW_ON_ERROR),
            'status' => 'PAUSED',
        ];
        $adResponse = $facebook->createAd($campaign->ad_account_id, $adPayload);
        $metaAdId = $this->providerId($adResponse, 'Meta ad creation failed.');

        $result = [
            'status' => 'synced_paused',
            'meta_campaign_id' => $metaCampaignId,
            'meta_adset_id' => $metaAdSetId,
            'meta_creative_id' => $metaCreativeId,
            'meta_ad_id' => $metaAdId,
            'payload_fingerprint' => $campaign->payload_fingerprint,
        ];

        $campaign->update([
            ...Arr::only($result, [
                'meta_campaign_id',
                'meta_adset_id',
                'meta_creative_id',
                'meta_ad_id',
            ]),
            'status' => 'synced_paused',
        ]);
        $this->recordReceipt($campaign, 'sync_paused', $idempotencyKey, [
            'campaign' => $campaignPayload,
            'adset' => $adSetPayload,
            'creative' => $creativePayload,
            'ad' => $adPayload,
        ], $result, $metaAdId);

        return $result;
    }

    public function preview(
        User $user,
        PaidMediaCampaign $campaign,
        string $adFormat
    ): array {
        $account = $this->accountForCampaign($user, $campaign);
        $allowedFormats = (array) config('social-media.meta_ads.preview_formats', []);

        if (! in_array($adFormat, $allowedFormats, true)) {
            throw new InvalidArgumentException('Unsupported Meta ad preview format.');
        }

        if (! $campaign->meta_creative_id) {
            throw new RuntimeException('Sync the campaign in PAUSED state before requesting a preview.');
        }

        $response = $this->facebookFor($account)->getAdPreviews($campaign->meta_creative_id, $adFormat);
        $this->assertSuccessful($response, 'Meta ad preview generation failed.');

        return [
            'ad_format' => $adFormat,
            'previews' => (array) $response->json('data', []),
        ];
    }

    public function activate(
        User $actor,
        PaidMediaCampaign $campaign,
        string $activationConfirmation,
        string $idempotencyKey
    ): array {
        $account = $this->accountForCampaign($actor, $campaign);
        $this->assertPermission($actor, $campaign, 'activation_permission');
        $this->assertCurrentApproval($campaign);

        if (! hash_equals($campaign->payload_fingerprint, $activationConfirmation)) {
            throw new RuntimeException('explicit_activation_required: confirm the current campaign fingerprint.');
        }

        if ($cached = $this->receiptResult($campaign, 'activate', $idempotencyKey)) {
            return $cached;
        }

        $this->assertProviderObjects($campaign);
        $facebook = $this->facebookFor($account);

        try {
            $this->assertSuccessful(
                $facebook->updateMarketingObject($campaign->meta_campaign_id, ['status' => 'ACTIVE']),
                'Meta campaign activation failed.'
            );
            $this->assertSuccessful(
                $facebook->updateMarketingObject($campaign->meta_adset_id, ['status' => 'ACTIVE']),
                'Meta ad set activation failed.'
            );
            $this->assertSuccessful(
                $facebook->updateMarketingObject($campaign->meta_ad_id, ['status' => 'ACTIVE']),
                'Meta ad activation failed.'
            );
        } catch (Throwable $exception) {
            $this->pauseProviderObjects($facebook, $campaign);
            throw $exception;
        }

        $result = [
            'status' => 'active',
            'activated_at' => now()->toIso8601String(),
            'payload_fingerprint' => $campaign->payload_fingerprint,
        ];
        $campaign->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);
        $this->recordReceipt($campaign, 'activate', $idempotencyKey, [
            'activation_confirmation' => $activationConfirmation,
            'provider_status' => 'ACTIVE',
        ], $result, $campaign->meta_ad_id);

        return $result;
    }

    public function pause(
        User $user,
        PaidMediaCampaign $campaign,
        string $idempotencyKey
    ): array {
        $account = $this->accountForCampaign($user, $campaign);

        if ($cached = $this->receiptResult($campaign, 'pause', $idempotencyKey)) {
            return $cached;
        }

        $this->assertProviderObjects($campaign);
        $facebook = $this->facebookFor($account);
        $this->pauseProviderObjects($facebook, $campaign, true);
        $campaign->update(['status' => 'paused']);
        $result = ['status' => 'paused', 'paused_at' => now()->toIso8601String()];
        $this->recordReceipt($campaign, 'pause', $idempotencyKey, [
            'provider_status' => 'PAUSED',
        ], $result, $campaign->meta_ad_id);

        return $result;
    }

    public function insights(
        User $user,
        PaidMediaCampaign $campaign,
        array $params = []
    ): array {
        $account = $this->accountForCampaign($user, $campaign);
        $objectId = $campaign->meta_ad_id ?: $campaign->meta_campaign_id;

        if (! $objectId) {
            throw new RuntimeException('Sync the campaign before requesting Meta insights.');
        }

        $response = $this->facebookFor($account)->getInsights(
            $objectId,
            (array) config('social-media.meta_ads.insight_fields', []),
            Arr::only($params, ['date_preset', 'time_range', 'level', 'breakdowns'])
        );
        $this->assertSuccessful($response, 'Meta insights request failed.');

        return [
            'object_id' => $objectId,
            'data' => (array) $response->json('data', []),
            'paging' => (array) $response->json('paging', []),
        ];
    }

    public function approvalFingerprint(PaidMediaCampaign|array $campaign): string
    {
        $payload = $campaign instanceof PaidMediaCampaign
            ? [
                'ad_account_id' => $campaign->ad_account_id,
                'name' => $campaign->name,
                'objective' => $campaign->objective,
                'budget_type' => $campaign->budget_type,
                'budget_minor' => $campaign->budget_minor,
                'spend_cap_minor' => $campaign->spend_cap_minor,
                'currency' => $campaign->currency,
                'starts_at' => $campaign->starts_at?->toIso8601String(),
                'ends_at' => $campaign->ends_at?->toIso8601String(),
                'campaign_payload' => $campaign->campaign_payload,
                'adset_payload' => $campaign->adset_payload,
                'creative_payload' => $campaign->creative_payload,
                'ad_payload' => $campaign->ad_payload,
            ]
            : $campaign;

        return hash('sha256', json_encode(
            $this->canonicalize($payload),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
        ));
    }

    private function validatedDraft(SocialMediaPlatform $account, array $payload): array
    {
        foreach (['ad_account_id', 'name', 'objective', 'budget', 'currency', 'campaign', 'adset', 'creative', 'ad'] as $field) {
            if (! array_key_exists($field, $payload) || $payload[$field] === '' || $payload[$field] === []) {
                throw new InvalidArgumentException("Missing required Meta Ads field: {$field}.");
            }
        }

        $adAccountId = $this->normalizeAdAccountId((string) $payload['ad_account_id']);
        $availableAccounts = collect((array) data_get($account->credentials, 'ad_accounts', []));
        $adAccount = $availableAccounts->first(function (array $candidate) use ($adAccountId) {
            return $this->normalizeAdAccountId((string) ($candidate['id'] ?? $candidate['account_id'] ?? '')) === $adAccountId;
        });

        if (! is_array($adAccount)) {
            throw new RuntimeException('The selected Meta ad account is not authorised by this Facebook connection.');
        }

        $currency = strtoupper((string) $payload['currency']);

        if (! preg_match('/^[A-Z]{3}$/', $currency)
            || strtoupper((string) ($adAccount['currency'] ?? $currency)) !== $currency) {
            throw new InvalidArgumentException('The Meta campaign currency must match the selected ad account.');
        }

        $objective = strtoupper((string) $payload['objective']);

        if (! in_array($objective, (array) config('social-media.meta_ads.objectives', []), true)) {
            throw new InvalidArgumentException('Unsupported Meta campaign objective.');
        }

        $budgetType = strtolower((string) data_get($payload, 'budget.type'));

        if (! in_array($budgetType, ['daily', 'lifetime'], true)) {
            throw new InvalidArgumentException('Meta budget type must be daily or lifetime.');
        }

        $budgetMinor = data_get($payload, 'budget.amount_minor');
        $spendCapMinor = data_get($payload, 'budget.spend_cap_minor');
        $maxBudget = max(1, (int) config('social-media.meta_ads.max_budget_minor', 10000000));

        if (! is_int($budgetMinor) || $budgetMinor <= 0 || $budgetMinor > $maxBudget) {
            throw new InvalidArgumentException('Meta budget must be a positive integer within the configured limit.');
        }

        if ($spendCapMinor !== null
            && (! is_int($spendCapMinor) || $spendCapMinor <= 0 || $spendCapMinor > $maxBudget)) {
            throw new InvalidArgumentException('Meta spend cap must be a positive integer within the configured limit.');
        }

        $startsAt = data_get($payload, 'schedule.starts_at')
            ? Carbon::parse((string) data_get($payload, 'schedule.starts_at'))
            : now()->addMinutes(15);
        $endsAt = data_get($payload, 'schedule.ends_at')
            ? Carbon::parse((string) data_get($payload, 'schedule.ends_at'))
            : null;

        if ($endsAt && ($endsAt->lte($startsAt)
            || $startsAt->diffInDays($endsAt) > (int) config('social-media.meta_ads.max_duration_days', 365))) {
            throw new InvalidArgumentException('Meta campaign schedule is invalid or exceeds the configured duration.');
        }

        if ($budgetType === 'lifetime' && ! $endsAt) {
            throw new InvalidArgumentException('Lifetime Meta budgets require an end time.');
        }

        if ((array) data_get($payload, 'creative.object_story_spec', []) === []) {
            throw new InvalidArgumentException('Meta creative object_story_spec is required.');
        }

        if ((array) data_get($payload, 'adset.targeting', []) === []) {
            throw new InvalidArgumentException('Meta ad set targeting is required.');
        }

        return [
            'ad_account_id' => $adAccountId,
            'name' => trim((string) $payload['name']),
            'objective' => $objective,
            'budget_type' => $budgetType,
            'budget_minor' => $budgetMinor,
            'spend_cap_minor' => $spendCapMinor,
            'currency' => $currency,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'campaign_payload' => (array) $payload['campaign'],
            'adset_payload' => (array) $payload['adset'],
            'creative_payload' => (array) $payload['creative'],
            'ad_payload' => (array) $payload['ad'],
        ];
    }

    private function assertItemAndAccount(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account
    ): void {
        if ((int) $item->user_id !== (int) $user->getKey()
            || (int) $account->user_id !== (int) $user->getKey()) {
            throw new RuntimeException('The paid-media context is outside the current tenant.');
        }

        if (! in_array($item->content_type, [
            DistributionItem::TYPE_PAID_CREATIVE,
            DistributionItem::TYPE_PRODUCT_OFFER,
            DistributionItem::TYPE_SERVICE_PROMOTION,
        ], true)) {
            throw new InvalidArgumentException('This distribution item cannot be used for Meta Ads.');
        }

        if ((string) $account->platform !== PlatformEnum::facebook->value || ! $account->isConnected()) {
            throw new RuntimeException('A connected Facebook channel is required for Meta Ads.');
        }

        $capability = $this->capabilities->forDestination($user, 'meta-ads', $account);

        if (! $capability['available']) {
            throw new RuntimeException('Meta Ads is unavailable: ' . ($capability['reason'] ?? 'unknown'));
        }
    }

    private function assertCampaignContext(
        User $user,
        PaidMediaCampaign $campaign,
        SocialMediaPlatform $account
    ): void {
        $this->assertCampaignOwner($user, $campaign);

        if ((int) $campaign->social_media_platform_id !== (int) $account->getKey()) {
            throw new RuntimeException('The paid-media campaign is linked to a different Facebook channel.');
        }
    }

    private function assertCampaignOwner(User $user, PaidMediaCampaign $campaign): void
    {
        if ((int) $campaign->user_id !== (int) $user->getKey()) {
            throw new RuntimeException('The paid-media campaign is outside the current tenant.');
        }
    }

    private function accountForCampaign(User $user, PaidMediaCampaign $campaign): SocialMediaPlatform
    {
        $this->assertCampaignOwner($user, $campaign);
        $account = SocialMediaPlatform::query()
            ->where('id', $campaign->social_media_platform_id)
            ->where('user_id', $user->getKey())
            ->where('platform', PlatformEnum::facebook->value)
            ->firstOrFail();

        $this->assertItemAndAccount($user, $campaign->distributionItem, $account);

        return $account;
    }

    private function facebookFor(SocialMediaPlatform $account): Facebook
    {
        try {
            $token = Crypt::decryptString((string) data_get(
                $account->credentials,
                'marketing_access_token_encrypted',
                ''
            ));
        } catch (Throwable $exception) {
            throw new RuntimeException('The Meta advertising token cannot be decrypted.', previous: $exception);
        }

        if ($token === '') {
            throw new RuntimeException('Reconnect Facebook to authorise Meta Ads.');
        }

        return new Facebook(null, $token);
    }

    private function assertPermission(
        User $actor,
        PaidMediaCampaign $campaign,
        string $configKey
    ): void {
        $ability = (string) config('social-media.meta_ads.' . $configKey);

        if ($ability !== '' && Gate::has($ability) && ! Gate::allows($ability, $campaign)) {
            throw new RuntimeException('The current user does not hold the required paid-media authority.');
        }

        if ((int) $actor->getKey() !== (int) $campaign->user_id) {
            throw new RuntimeException('Paid-media authority cannot cross tenants.');
        }
    }

    private function assertCurrentApproval(PaidMediaCampaign $campaign): object
    {
        $approval = $this->latestApproval($campaign);

        if (! $approval
            || $approval->decision !== 'approved'
            || ! hash_equals((string) $approval->payload_fingerprint, (string) $campaign->payload_fingerprint)
            || (int) $approval->budget_minor !== (int) $campaign->budget_minor
            || (int) ($approval->spend_cap_minor ?? 0) !== (int) ($campaign->spend_cap_minor ?? 0)
            || (string) $approval->currency !== (string) $campaign->currency) {
            throw new RuntimeException('The current Meta campaign requires a new budget approval.');
        }

        return $approval;
    }

    private function latestApproval(PaidMediaCampaign $campaign): ?object
    {
        if (! Schema::hasTable('ext_social_media_paid_media_approvals')) {
            return null;
        }

        return DB::table('ext_social_media_paid_media_approvals')
            ->where('paid_media_campaign_id', $campaign->getKey())
            ->orderByDesc('id')
            ->first();
    }

    private function appendApprovalDecision(
        PaidMediaCampaign $campaign,
        User $actor,
        string $decision,
        string $fingerprint,
        string $reason
    ): void {
        if (! Schema::hasTable('ext_social_media_paid_media_approvals')) {
            return;
        }

        DB::table('ext_social_media_paid_media_approvals')->insert([
            'paid_media_campaign_id' => $campaign->getKey(),
            'user_id' => $campaign->user_id,
            'actor_id' => $actor->getKey(),
            'decision' => $decision,
            'payload_fingerprint' => $fingerprint,
            'budget_minor' => $campaign->budget_minor,
            'spend_cap_minor' => $campaign->spend_cap_minor,
            'currency' => $campaign->currency,
            'starts_at' => $campaign->starts_at,
            'ends_at' => $campaign->ends_at,
            'supersedes_id' => $this->latestApproval($campaign)?->id,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    private function receiptResult(
        PaidMediaCampaign $campaign,
        string $action,
        string $idempotencyKey
    ): ?array {
        if (! Schema::hasTable('ext_social_media_paid_media_receipts')) {
            return null;
        }

        $receipt = DB::table('ext_social_media_paid_media_receipts')
            ->where('user_id', $campaign->user_id)
            ->where('paid_media_campaign_id', $campaign->getKey())
            ->where('action', $action)
            ->where('idempotency_key', $idempotencyKey)
            ->where('status', 'succeeded')
            ->first();

        return $receipt ? (array) json_decode($receipt->response_snapshot, true, flags: JSON_THROW_ON_ERROR) : null;
    }

    private function recordReceipt(
        PaidMediaCampaign $campaign,
        string $action,
        ?string $idempotencyKey,
        array $request,
        array $response,
        ?string $providerObjectId = null
    ): void {
        if (! Schema::hasTable('ext_social_media_paid_media_receipts')) {
            return;
        }

        DB::table('ext_social_media_paid_media_receipts')->insert([
            'paid_media_campaign_id' => $campaign->getKey(),
            'user_id' => $campaign->user_id,
            'action' => $action,
            'idempotency_key' => $idempotencyKey,
            'request_hash' => hash('sha256', json_encode($this->canonicalize($request), JSON_THROW_ON_ERROR)),
            'request_snapshot' => json_encode($request, JSON_THROW_ON_ERROR),
            'response_snapshot' => json_encode($response, JSON_THROW_ON_ERROR),
            'provider_object_id' => $providerObjectId,
            'status' => 'succeeded',
            'created_at' => now(),
        ]);
    }

    private function providerId(Response $response, string $message): string
    {
        $this->assertSuccessful($response, $message);
        $id = (string) $response->json('id', '');

        if ($id === '') {
            throw new RuntimeException($message . ' Meta did not return an object ID.');
        }

        return $id;
    }

    private function assertSuccessful(Response $response, string $message): void
    {
        if ($response->successful()) {
            return;
        }

        $code = (string) ($response->json('error.code') ?? $response->status());
        $providerMessage = (string) ($response->json('error.message') ?? 'Provider request failed.');

        throw new RuntimeException("{$message} [{$code}] {$providerMessage}");
    }

    private function assertProviderObjects(PaidMediaCampaign $campaign): void
    {
        if (! $campaign->meta_campaign_id || ! $campaign->meta_adset_id || ! $campaign->meta_ad_id) {
            throw new RuntimeException('Sync the Meta campaign in PAUSED state before changing delivery status.');
        }
    }

    private function pauseProviderObjects(
        Facebook $facebook,
        PaidMediaCampaign $campaign,
        bool $throwOnFailure = false
    ): void {
        foreach ([$campaign->meta_ad_id, $campaign->meta_adset_id, $campaign->meta_campaign_id] as $objectId) {
            if (! $objectId) {
                continue;
            }

            $response = $facebook->updateMarketingObject($objectId, ['status' => 'PAUSED']);

            if ($throwOnFailure) {
                $this->assertSuccessful($response, 'Meta pause operation failed.');
            }
        }
    }

    private function normalizeAdAccountId(string $value): string
    {
        $value = trim($value);

        return str_starts_with($value, 'act_') ? $value : 'act_' . $value;
    }

    private function canonicalize(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonicalize($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
