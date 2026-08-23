<?php

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\Facebook;
use App\Extensions\SocialMedia\System\Helpers\Instagram;
use App\Extensions\SocialMedia\System\Helpers\Linkedin;
use App\Extensions\SocialMedia\System\Helpers\X;
use App\Extensions\SocialMedia\System\Helpers\Youtube;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class EngagementGovernanceService
{
    private const DESTINATION = 'engagement';

    private const SUPPORTED_PROVIDERS = [
        'facebook',
        'instagram',
        'youtube',
        'youtube-shorts',
        'linkedin',
        'x',
        'tiktok',
    ];

    public function __construct(private readonly DistributionCapabilityService $distributionCapabilities) {}

    public function capabilities(User $user, SocialMediaPlatform $account): array
    {
        $this->assertAccount($user, $account);
        $provider = $this->providerKey($account);
        $definition = (array) config("social-media.engagement.providers.{$provider}", []);

        if ($definition === []) {
            return $this->unavailableCapabilities($provider, 'provider_not_configured');
        }

        if (($definition['mode'] ?? null) === 'unavailable') {
            $result = $this->unavailableCapabilities(
                $provider,
                (string) ($definition['reason'] ?? 'provider_operation_unavailable')
            );
            $this->persistCapabilitySnapshot($account, $result);
            $this->audit($user, $account, 'engagement_capability_snapshot', $result);

            return $result;
        }

        $grantedScopes = $this->grantedScopes($account, $provider);
        $organizations = [];

        if ($provider === 'linkedin'
            && array_intersect(['r_organization_social_feed', 'w_organization_social_feed'], $grantedScopes) !== []) {
            $organizations = $this->linkedinOrganizations($account);
        }

        $operations = [];
        foreach ((array) ($definition['operations'] ?? []) as $operation => $operationDefinition) {
            $requiredScopes = array_values((array) ($operationDefinition['required_scopes'] ?? []));
            $scopeReady = count(array_diff($requiredScopes, $grantedScopes)) === 0;
            $productReady = $provider !== 'linkedin'
                || ! (bool) ($operationDefinition['supported'] ?? false)
                || $organizations !== [];
            $operations[$operation] = (bool) ($operationDefinition['supported'] ?? false)
                && $scopeReady
                && $productReady;
        }

        $result = [
            'provider' => $provider,
            'mode' => (string) ($definition['mode'] ?? 'unavailable'),
            'available' => in_array(true, $operations, true),
            'reason' => in_array(true, $operations, true) ? null : 'required_provider_permissions_unavailable',
            'granted_scopes' => $grantedScopes,
            'operations' => $operations,
            'organizations' => $organizations,
            'checked_at' => now()->toIso8601String(),
        ];

        $this->persistCapabilitySnapshot($account, $result);
        $this->audit($user, $account, 'engagement_capability_snapshot', [
            ...$result,
            'granted_scopes' => $grantedScopes,
        ]);

        return $result;
    }

    public function inbox(User $user, SocialMediaPlatform $account, array $filters = []): array
    {
        $capabilities = $this->requireOperation($user, $account, 'inbox');
        $provider = $capabilities['provider'];
        $limit = max(1, min(100, (int) ($filters['limit'] ?? 50)));
        $items = [];
        $paging = [];

        switch ($provider) {
            case 'facebook':
                $resourceId = $this->requiredString($filters, 'resource_id', 500);
                $response = (new Facebook(null, $this->accessToken($account)))
                    ->comments($resourceId, $limit, $filters['after'] ?? null);
                $this->assertSuccessful($response, 'Facebook comments could not be read.');
                $items = array_map(
                    fn (array $item) => $this->normaliseMetaEngagement('facebook', $resourceId, $item),
                    array_values((array) $response->json('data', []))
                );
                $paging = (array) $response->json('paging', []);
                break;

            case 'instagram':
                $resourceId = $this->requiredString($filters, 'resource_id', 500);
                $response = (new Instagram(null, $this->accessToken($account)))
                    ->comments($resourceId, $limit, $filters['after'] ?? null);
                $this->assertSuccessful($response, 'Instagram comments could not be read.');
                $items = array_map(
                    fn (array $item) => $this->normaliseInstagramEngagement($resourceId, $item),
                    array_values((array) $response->json('data', []))
                );
                $paging = (array) $response->json('paging', []);
                break;

            case 'youtube':
                $videoId = isset($filters['video_id']) ? trim((string) $filters['video_id']) : null;
                $channelId = $videoId ? null : trim((string) data_get($account->credentials, 'channel_id', data_get($account->credentials, 'platform_id', '')));
                if (! $videoId && ! $channelId) {
                    throw new InvalidArgumentException('A YouTube video or connected channel is required.');
                }
                $response = $this->youtube($account)->commentThreads(
                    $videoId,
                    $channelId,
                    $limit,
                    $filters['page_token'] ?? null
                );
                $this->assertSuccessful($response, 'YouTube comments could not be read.');
                $items = array_map(
                    fn (array $item) => $this->normaliseYoutubeEngagement($item),
                    array_values((array) $response->json('items', []))
                );
                $paging = ['next_page_token' => $response->json('nextPageToken')];
                break;

            case 'linkedin':
                $targetUrn = $this->requiredString($filters, 'target_urn', 1000);
                $response = (new Linkedin(null, $this->accessToken($account)))
                    ->comments($targetUrn, (int) ($filters['start'] ?? 0), $limit);
                $this->assertSuccessful($response, 'LinkedIn comments could not be read.');
                $items = array_map(
                    fn (array $item) => $this->normaliseLinkedinEngagement($targetUrn, $item),
                    array_values((array) $response->json('elements', []))
                );
                $paging = (array) $response->json('paging', []);
                break;

            case 'x':
                $userId = trim((string) data_get($account->credentials, 'platform_id', ''));
                if ($userId === '') {
                    throw new InvalidArgumentException('The connected X user ID is missing.');
                }
                $response = (new X(null, $this->accessToken($account)))
                    ->mentions($userId, $limit, $filters['pagination_token'] ?? null);
                $this->assertSuccessful($response, 'X mentions could not be read.');
                $users = collect((array) $response->json('includes.users', []))->keyBy('id');
                $items = array_map(
                    fn (array $item) => $this->normaliseXEngagement($item, (array) $users->get($item['author_id'] ?? '', [])),
                    array_values((array) $response->json('data', []))
                );
                $paging = (array) $response->json('meta', []);
                break;

            default:
                throw new RuntimeException('This provider does not expose a governed commercial engagement inbox.');
        }

        $items = array_map(fn (array $item) => $this->withPolicyClassification($user, $item), $items);
        $this->audit($user, $account, 'engagement_inbox_read', [
            'provider' => $provider,
            'count' => count($items),
            'resource_hash' => hash('sha256', (string) ($filters['resource_id'] ?? $filters['video_id'] ?? $filters['target_urn'] ?? 'account')),
        ]);

        return [
            'provider' => $provider,
            'items' => $items,
            'paging' => $paging,
            'capabilities' => $capabilities['operations'],
        ];
    }

    public function proposeReply(
        User $user,
        SocialMediaPlatform $account,
        array $engagement,
        ?string $suggestedText = null
    ): array {
        $this->assertAccount($user, $account);
        $normalised = $this->normaliseInputEngagement($this->providerKey($account), $engagement);
        $context = $this->engagementContext($user, $normalised, $engagement);
        $reply = trim((string) ($suggestedText ?? ''));

        if ($reply === '') {
            $reply = 'Thanks for your ' . ($context['policy']['terminology'] ?? 'enquiry') . '. A team member will review this before a reply is sent.';
        }

        if (mb_strlen($reply) > 10000) {
            throw new InvalidArgumentException('The proposed reply is too long.');
        }

        $replyHash = hash('sha256', $reply);
        $proposalId = (string) Str::uuid();
        $proposal = [
            'proposal_id' => $proposalId,
            'provider' => $this->providerKey($account),
            'engagement_id' => $normalised['engagement_id'],
            'resource_id' => $normalised['resource_id'],
            'suggested_text' => $reply,
            'reply_hash' => $replyHash,
            'approved' => false,
            'auto_send_allowed' => false,
            'suppression' => $context['suppression'],
            'risk' => $context['risk'],
            'classification' => $context['classification'],
            'quiet_period' => $context['quiet_period'],
            'human_handoff_required' => $context['human_handoff_required'],
            'handoff_targets' => $context['handoff_targets'],
            'profile_version' => $context['profile']['profile_version'] ?? null,
            'profile_provenance' => $context['profile']['profile_provenance'] ?? [],
            'expires_at' => now()->addSeconds((int) config('social-media.engagement.proposal_ttl_seconds', 86400))->toIso8601String(),
        ];

        $this->audit($user, $account, 'engagement_reply_proposed', [
            'proposal_id' => $proposalId,
            'provider' => $proposal['provider'],
            'engagement_id_hash' => hash('sha256', $normalised['engagement_id']),
            'resource_id_hash' => hash('sha256', $normalised['resource_id']),
            'reply_hash' => $replyHash,
            'classification' => $context['classification'],
            'risk' => $context['risk'],
            'suppression' => $context['suppression'],
            'quiet_period' => $context['quiet_period'],
            'human_handoff_required' => $context['human_handoff_required'],
            'handoff_targets' => $context['handoff_targets'],
            'profile_version' => $proposal['profile_version'],
            'profile_provenance' => $proposal['profile_provenance'],
            'expires_at' => $proposal['expires_at'],
        ]);

        return $proposal;
    }

    public function sendReply(User $user, SocialMediaPlatform $account, array $input): array
    {
        return $this->sendGovernedReply($user, $account, $input, false);
    }

    public function privateReply(User $user, SocialMediaPlatform $account, array $input): array
    {
        return $this->sendGovernedReply($user, $account, $input, true);
    }

    public function editReply(User $user, SocialMediaPlatform $account, array $input): array
    {
        $capabilities = $this->requireOperation($user, $account, 'edit');
        $this->requireExplicitApproval($input);
        $provider = $capabilities['provider'];
        $replyId = $this->requiredString($input, 'reply_id', 1000);
        $replyText = $this->requiredString($input, 'reply_text', 10000);
        $operationKey = $this->operationKey($account, 'edit', $replyId, hash('sha256', $replyText));

        return $this->withDuplicateLock($operationKey, function () use ($user, $account, $input, $provider, $replyId, $replyText, $operationKey) {
            $response = match ($provider) {
                'youtube' => $this->youtube($account)->updateComment($replyId, $replyText),
                'linkedin' => (new Linkedin(null, $this->accessToken($account)))->updateComment(
                    $this->requiredString($input, 'object_urn', 1000),
                    $replyId,
                    $this->requiredString($input, 'actor_urn', 1000),
                    $replyText
                ),
                default => throw new RuntimeException('Reply editing is not supported for this provider.'),
            };
            $this->assertSuccessful($response, 'The provider reply could not be edited.');
            $result = $this->providerReceipt($provider, 'edited', $response, $operationKey);
            $this->audit($user, $account, 'engagement_reply_edited', $result);

            return $result;
        });
    }

    public function deleteReply(User $user, SocialMediaPlatform $account, array $input): array
    {
        $capabilities = $this->requireOperation($user, $account, 'delete');
        $this->requireExplicitApproval($input);
        $provider = $capabilities['provider'];
        $replyId = $this->requiredString($input, 'reply_id', 1000);
        $operationKey = $this->operationKey($account, 'delete', $replyId, 'delete');

        return $this->withDuplicateLock($operationKey, function () use ($user, $account, $input, $provider, $replyId, $operationKey) {
            $response = match ($provider) {
                'youtube' => $this->youtube($account)->deleteComment($replyId),
                'linkedin' => (new Linkedin(null, $this->accessToken($account)))->deleteComment(
                    $this->requiredString($input, 'object_urn', 1000),
                    $replyId,
                    $this->requiredString($input, 'actor_urn', 1000)
                ),
                'x' => (new X(null, $this->accessToken($account)))->deletePost($replyId),
                default => throw new RuntimeException('Reply deletion is not supported for this provider.'),
            };
            $this->assertSuccessful($response, 'The provider reply could not be deleted.');
            $result = $this->providerReceipt($provider, 'deleted', $response, $operationKey);
            $this->audit($user, $account, 'engagement_reply_deleted', $result);

            return $result;
        });
    }

    public function handoff(User $user, SocialMediaPlatform $account, array $engagement): array
    {
        $this->assertAccount($user, $account);
        $normalised = $this->normaliseInputEngagement($this->providerKey($account), $engagement);
        $context = $this->engagementContext($user, $normalised, $engagement);
        $handoff = [
            'status' => 'human_handoff_required',
            'provider' => $this->providerKey($account),
            'engagement_id' => $normalised['engagement_id'],
            'classification' => $context['classification'],
            'risk' => $context['risk'],
            'handoff_targets' => $context['handoff_targets'],
            'profile_version' => $context['profile']['profile_version'] ?? null,
            'profile_provenance' => $context['profile']['profile_provenance'] ?? [],
            'automated_reply_sent' => false,
        ];
        $this->audit($user, $account, 'engagement_handoff', [
            ...$handoff,
            'engagement_id' => hash('sha256', $normalised['engagement_id']),
        ]);

        return $handoff;
    }

    public function ingestWebhookEvent(SocialMediaPlatform $account, array $engagement): array
    {
        $user = $account->user()->first();

        if (! $user instanceof User) {
            throw new RuntimeException('The engagement account owner is unavailable.');
        }

        $this->assertAccount($user, $account);
        $normalised = $this->normaliseInputEngagement($this->providerKey($account), $engagement);
        $context = $this->engagementContext($user, $normalised, $engagement);
        $operationKey = $this->operationKey($account, 'ingest', $normalised['engagement_id'], 'webhook');
        $duplicate = $this->duplicateExists($user, $account, 'engagement_ingested', $operationKey);

        if (! $duplicate) {
            $this->audit($user, $account, 'engagement_ingested', [
                'operation_key' => $operationKey,
                'provider' => $this->providerKey($account),
                'engagement_id_hash' => hash('sha256', $normalised['engagement_id']),
                'resource_id_hash' => hash('sha256', $normalised['resource_id']),
                'classification' => $context['classification'],
                'risk' => $context['risk'],
                'suppression' => $context['suppression'],
                'human_handoff_required' => $context['human_handoff_required'],
                'handoff_targets' => $context['handoff_targets'],
            ]);
        }

        return [
            ...$normalised,
            'classification' => $context['classification'],
            'risk' => $context['risk'],
            'suppression' => $context['suppression'],
            'quiet_period' => $context['quiet_period'],
            'human_handoff_required' => $context['human_handoff_required'],
            'handoff_targets' => $context['handoff_targets'],
            'duplicate' => $duplicate,
        ];
    }

    private function sendGovernedReply(User $user, SocialMediaPlatform $account, array $input, bool $private): array
    {
        $operation = $private ? 'private_reply' : 'public_reply';
        $capabilities = $this->requireOperation($user, $account, $operation);
        $this->requireExplicitApproval($input);
        $provider = $capabilities['provider'];
        $engagement = $this->normaliseInputEngagement($provider, (array) ($input['engagement'] ?? []));
        $replyText = $this->requiredString($input, 'reply_text', 10000);
        $context = $this->engagementContext($user, $engagement, (array) ($input['engagement'] ?? []));
        $this->assertProposal($user, $account, $input, $engagement, $replyText);
        $this->assertPolicyAllowsSend($context, $input);
        $operationKey = $this->operationKey($account, $operation, $engagement['engagement_id'], hash('sha256', $replyText));

        return $this->withDuplicateLock($operationKey, function () use ($user, $account, $input, $provider, $engagement, $replyText, $private, $operationKey, $context) {
            if ($this->duplicateExists($user, $account, $private ? 'engagement_private_reply_sent' : 'engagement_reply_sent', $operationKey)) {
                throw new RuntimeException('duplicate_engagement_reply');
            }

            $response = $private
                ? $this->dispatchPrivateReply($provider, $account, $engagement, $replyText)
                : $this->dispatchPublicReply($provider, $account, $engagement, $replyText, $input);
            $this->assertSuccessful($response, 'The provider reply could not be sent.');
            $result = [
                ...$this->providerReceipt($provider, 'sent', $response, $operationKey),
                'engagement_id' => $engagement['engagement_id'],
                'reply_hash' => hash('sha256', $replyText),
                'classification' => $context['classification'],
                'risk' => $context['risk'],
                'handoff_targets' => $context['handoff_targets'],
                'proposal_id' => (string) $input['proposal_id'],
            ];
            $this->audit($user, $account, $private ? 'engagement_private_reply_sent' : 'engagement_reply_sent', [
                ...$result,
                'engagement_id' => hash('sha256', $engagement['engagement_id']),
            ]);

            return $result;
        });
    }

    private function dispatchPublicReply(string $provider, SocialMediaPlatform $account, array $engagement, string $replyText, array $input): Response
    {
        return match ($provider) {
            'facebook' => (new Facebook(null, $this->accessToken($account)))->replyToComment($engagement['engagement_id'], $replyText),
            'instagram' => (new Instagram(null, $this->accessToken($account)))->replyToComment($engagement['engagement_id'], $replyText),
            'youtube' => $this->youtube($account)->replyToComment($engagement['engagement_id'], $replyText),
            'linkedin' => (new Linkedin(null, $this->accessToken($account)))->replyToComment(
                $this->requiredString($input, 'target_urn', 1000),
                $this->requiredString($input, 'actor_urn', 1000),
                $this->requiredString($input, 'object_urn', 1000),
                $replyText,
                $engagement['provider_context']['comment_urn'] ?? null
            ),
            'x' => (new X(null, $this->accessToken($account)))->replyToPost($engagement['engagement_id'], $replyText),
            default => throw new RuntimeException('Public replies are unavailable for this provider.'),
        };
    }

    private function dispatchPrivateReply(string $provider, SocialMediaPlatform $account, array $engagement, string $replyText): Response
    {
        $platformId = trim((string) data_get($account->credentials, 'platform_id', ''));

        if ($platformId === '') {
            throw new RuntimeException('The connected provider account ID is unavailable.');
        }

        return match ($provider) {
            'facebook' => (new Facebook(null, $this->accessToken($account)))->privateReply($platformId, $engagement['engagement_id'], $replyText),
            'instagram' => (new Instagram(null, $this->accessToken($account)))->privateReply($platformId, $engagement['engagement_id'], $replyText),
            default => throw new RuntimeException('Private replies are unavailable for this provider.'),
        };
    }

    private function engagementContext(User $user, array $engagement, array $input): array
    {
        $profile = $this->distributionCapabilities->resolveVerticalProfile(
            isset($input['vertical']) ? (string) $input['vertical'] : null,
            isset($input['subtype']) ? (string) $input['subtype'] : null,
            $user
        );
        $slug = (string) ($profile['slug'] ?? 'generic-business');
        $generic = (array) config('social-media.engagement.policies.generic-business', []);
        $overlay = (array) config("social-media.engagement.policies.{$slug}", []);
        $tenantEngagementPolicy = (array) ($profile['engagement_policy'] ?? []);
        $policy = array_replace_recursive($generic, $overlay, $tenantEngagementPolicy);
        $message = mb_strtolower(trim((string) ($engagement['message'] ?? '')));
        $suppression = $this->containsAny($message, (array) ($policy['suppression'] ?? []));
        $highRisk = $this->containsAny($message, (array) ($policy['high_risk'] ?? []));
        $quietPeriod = $this->quietPeriod($policy);
        $classification = $suppression ? 'suppression' : ($highRisk ? 'high-risk' : 'enquiry');
        $risk = $highRisk ? 'high' : 'normal';
        $handoffTargets = $this->handoffTargets($profile, $classification);

        return [
            'profile' => $profile,
            'policy' => $policy,
            'classification' => $classification,
            'risk' => $risk,
            'suppression' => $suppression,
            'quiet_period' => $quietPeriod,
            'human_handoff_required' => $suppression || $highRisk,
            'handoff_targets' => $handoffTargets,
        ];
    }

    private function assertPolicyAllowsSend(array $context, array $input): void
    {
        if ($context['suppression']) {
            throw new RuntimeException('engagement_suppressed');
        }

        if ($context['human_handoff_required'] && ! (bool) ($input['human_handoff_acknowledged'] ?? false)) {
            throw new RuntimeException('human_handoff_required');
        }

        if ($context['quiet_period']['active']
            && ! (bool) ($input['quiet_period_override'] ?? false)
            && ! $context['human_handoff_required']) {
            throw new RuntimeException('quiet_period_active');
        }
    }

    private function requireOperation(User $user, SocialMediaPlatform $account, string $operation): array
    {
        $capabilities = $this->capabilities($user, $account);

        if (! (bool) data_get($capabilities, "operations.{$operation}", false)) {
            throw new RuntimeException("engagement_operation_unavailable:{$operation}");
        }

        return $capabilities;
    }

    private function grantedScopes(SocialMediaPlatform $account, string $provider): array
    {
        if (in_array($provider, ['facebook', 'instagram'], true)) {
            try {
                $helper = $provider === 'facebook'
                    ? new Facebook(null, $this->accessToken($account))
                    : new Instagram(null, $this->accessToken($account));
                $response = $helper->debugToken();

                if ($response->successful() && (bool) $response->json('data.is_valid', false)) {
                    $scopes = (array) $response->json('data.scopes', []);
                    foreach ((array) $response->json('data.granular_scopes', []) as $granular) {
                        if (! empty($granular['scope'])) {
                            $scopes[] = $granular['scope'];
                        }
                    }

                    return array_values(array_unique(array_filter(array_map('strval', $scopes))));
                }
            } catch (Throwable) {
                return [];
            }

            return [];
        }

        $credentials = (array) $account->credentials;
        $scopes = $credentials['authorized_scopes'] ?? $credentials['scope'] ?? [];

        if (is_string($scopes)) {
            $scopes = preg_split('/[\s,]+/', trim($scopes)) ?: [];
        }

        return array_values(array_unique(array_filter(array_map('strval', (array) $scopes))));
    }

    private function linkedinOrganizations(SocialMediaPlatform $account): array
    {
        try {
            $response = (new Linkedin(null, $this->accessToken($account)))->organizationAcls();

            if ($response->failed()) {
                return [];
            }

            return array_values(array_unique(array_filter(array_map(
                static fn (array $item): ?string => isset($item['organization']) ? (string) $item['organization'] : null,
                array_values((array) $response->json('elements', []))
            ))));
        } catch (Throwable) {
            return [];
        }
    }

    private function persistCapabilitySnapshot(SocialMediaPlatform $account, array $snapshot): void
    {
        $credentials = (array) $account->credentials;
        $credentials['engagement_capabilities'] = [
            'provider' => $snapshot['provider'] ?? null,
            'available' => $snapshot['available'] ?? false,
            'reason' => $snapshot['reason'] ?? null,
            'operations' => $snapshot['operations'] ?? [],
            'organizations' => $snapshot['organizations'] ?? [],
            'checked_at' => $snapshot['checked_at'] ?? now()->toIso8601String(),
        ];
        $account->update(['credentials' => $credentials]);
    }

    private function unavailableCapabilities(string $provider, string $reason): array
    {
        return [
            'provider' => $provider,
            'mode' => 'unavailable',
            'available' => false,
            'reason' => $reason,
            'granted_scopes' => [],
            'operations' => [
                'inbox' => false,
                'public_reply' => false,
                'private_reply' => false,
                'edit' => false,
                'delete' => false,
            ],
            'organizations' => [],
            'checked_at' => now()->toIso8601String(),
        ];
    }

    private function normaliseMetaEngagement(string $provider, string $resourceId, array $item): array
    {
        return [
            'provider' => $provider,
            'engagement_id' => (string) ($item['id'] ?? ''),
            'resource_id' => $resourceId,
            'parent_id' => (string) data_get($item, 'parent.id', ''),
            'actor_id' => (string) data_get($item, 'from.id', ''),
            'actor_alias' => (string) data_get($item, 'from.name', ''),
            'message' => (string) ($item['message'] ?? ''),
            'received_at' => $item['created_time'] ?? null,
            'provider_context' => [
                'can_reply_privately' => (bool) ($item['can_reply_privately'] ?? false),
            ],
        ];
    }

    private function normaliseInstagramEngagement(string $resourceId, array $item): array
    {
        return [
            'provider' => 'instagram',
            'engagement_id' => (string) ($item['id'] ?? ''),
            'resource_id' => $resourceId,
            'parent_id' => (string) ($item['parent_id'] ?? ''),
            'actor_id' => (string) data_get($item, 'from.id', ''),
            'actor_alias' => (string) ($item['username'] ?? data_get($item, 'from.username', '')),
            'message' => (string) ($item['text'] ?? ''),
            'received_at' => $item['timestamp'] ?? null,
            'provider_context' => [],
        ];
    }

    private function normaliseYoutubeEngagement(array $item): array
    {
        $comment = (array) data_get($item, 'snippet.topLevelComment', []);

        return [
            'provider' => 'youtube',
            'engagement_id' => (string) ($comment['id'] ?? ''),
            'resource_id' => (string) data_get($item, 'snippet.videoId', data_get($item, 'snippet.channelId', '')),
            'parent_id' => '',
            'actor_id' => (string) data_get($comment, 'snippet.authorChannelId.value', ''),
            'actor_alias' => (string) data_get($comment, 'snippet.authorDisplayName', ''),
            'message' => (string) data_get($comment, 'snippet.textOriginal', data_get($comment, 'snippet.textDisplay', '')),
            'received_at' => data_get($comment, 'snippet.publishedAt'),
            'provider_context' => [
                'can_reply' => (bool) data_get($item, 'snippet.canReply', false),
                'total_reply_count' => (int) data_get($item, 'snippet.totalReplyCount', 0),
            ],
        ];
    }

    private function normaliseLinkedinEngagement(string $targetUrn, array $item): array
    {
        return [
            'provider' => 'linkedin',
            'engagement_id' => (string) ($item['id'] ?? ''),
            'resource_id' => (string) ($item['object'] ?? $targetUrn),
            'parent_id' => (string) ($item['parentComment'] ?? ''),
            'actor_id' => (string) ($item['actor'] ?? ''),
            'actor_alias' => '',
            'message' => (string) data_get($item, 'message.text', ''),
            'received_at' => data_get($item, 'created.time'),
            'provider_context' => [
                'comment_urn' => (string) ($item['commentUrn'] ?? ''),
                'target_urn' => $targetUrn,
            ],
        ];
    }

    private function normaliseXEngagement(array $item, array $author): array
    {
        return [
            'provider' => 'x',
            'engagement_id' => (string) ($item['id'] ?? ''),
            'resource_id' => (string) ($item['conversation_id'] ?? $item['id'] ?? ''),
            'parent_id' => '',
            'actor_id' => (string) ($item['author_id'] ?? ''),
            'actor_alias' => (string) ($author['username'] ?? $author['name'] ?? ''),
            'message' => (string) ($item['text'] ?? ''),
            'received_at' => $item['created_at'] ?? null,
            'provider_context' => [
                'referenced_tweets' => array_slice((array) ($item['referenced_tweets'] ?? []), 0, 5),
            ],
        ];
    }

    private function normaliseInputEngagement(string $provider, array $input): array
    {
        $engagementId = $this->requiredString($input, 'engagement_id', 1000);
        $resourceId = trim((string) ($input['resource_id'] ?? $engagementId));

        return [
            'provider' => $provider,
            'engagement_id' => $engagementId,
            'resource_id' => $resourceId !== '' ? $resourceId : $engagementId,
            'parent_id' => trim((string) ($input['parent_id'] ?? '')),
            'actor_id' => trim((string) ($input['actor_id'] ?? '')),
            'actor_alias' => trim((string) ($input['actor_alias'] ?? '')),
            'message' => trim((string) ($input['message'] ?? '')),
            'received_at' => $input['received_at'] ?? null,
            'provider_context' => array_slice((array) ($input['provider_context'] ?? []), 0, 20, true),
        ];
    }

    private function withPolicyClassification(User $user, array $engagement): array
    {
        $context = $this->engagementContext($user, $engagement, []);

        return [
            ...$engagement,
            'classification' => $context['classification'],
            'risk' => $context['risk'],
            'suppression' => $context['suppression'],
            'human_handoff_required' => $context['human_handoff_required'],
            'handoff_targets' => $context['handoff_targets'],
        ];
    }

    private function handoffTargets(array $profile, string $classification): array
    {
        $targets = (array) ($profile['handoff_targets'] ?? []);
        $preferred = $classification === 'high-risk'
            ? ($targets['incident'] ?? $targets['enquiry'] ?? [])
            : ($targets['enquiry'] ?? []);

        if (! is_array($preferred)) {
            $preferred = [$preferred];
        }

        if ($preferred === []) {
            foreach ($targets as $value) {
                $preferred = array_merge($preferred, is_array($value) ? $value : [$value]);
            }
        }

        return array_values(array_unique(array_filter(array_map('strval', $preferred))));
    }

    private function quietPeriod(array $policy): array
    {
        $definition = (array) ($policy['quiet_period'] ?? []);
        $enabled = (bool) ($definition['enabled'] ?? false);
        $start = (string) ($definition['start'] ?? '22:00');
        $end = (string) ($definition['end'] ?? '07:00');
        $active = false;

        if ($enabled && preg_match('/^\d{2}:\d{2}$/', $start) && preg_match('/^\d{2}:\d{2}$/', $end)) {
            $now = now();
            $current = $now->format('H:i');
            $active = $start <= $end
                ? ($current >= $start && $current < $end)
                : ($current >= $start || $current < $end);
        }

        return ['enabled' => $enabled, 'active' => $active, 'start' => $start, 'end' => $end];
    }

    private function containsAny(string $message, array $needles): bool
    {
        foreach ($needles as $needle) {
            $needle = mb_strtolower(trim((string) $needle));
            if ($needle !== '' && str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function assertProposal(User $user, SocialMediaPlatform $account, array $input, array $engagement, string $replyText): void
    {
        $proposalId = $this->requiredString($input, 'proposal_id', 100);
        $proposal = $this->proposalReceipt($user, $account, $proposalId);

        if (! $proposal
            || ! hash_equals((string) ($proposal['reply_hash'] ?? ''), hash('sha256', $replyText))
            || ! hash_equals((string) ($proposal['engagement_id_hash'] ?? ''), hash('sha256', $engagement['engagement_id']))) {
            throw new RuntimeException('approved_proposal_mismatch');
        }

        if (isset($proposal['expires_at']) && now()->greaterThan($proposal['expires_at'])) {
            throw new RuntimeException('approved_proposal_expired');
        }
    }

    private function proposalReceipt(User $user, SocialMediaPlatform $account, string $proposalId): ?array
    {
        if (! Schema::hasTable('ext_social_media_distribution_audits')) {
            return null;
        }

        $rows = DB::table('ext_social_media_distribution_audits')
            ->where('user_id', $user->getKey())
            ->where('social_media_platform_id', $account->getKey())
            ->where('destination', self::DESTINATION)
            ->where('action', 'engagement_reply_proposed')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        foreach ($rows as $row) {
            $snapshot = $this->decodeSnapshot($row->snapshot ?? null);
            if (($snapshot['proposal_id'] ?? null) === $proposalId) {
                return $snapshot;
            }
        }

        return null;
    }

    private function withDuplicateLock(string $operationKey, callable $callback): mixed
    {
        return Cache::lock('titan-reach:engagement:' . $operationKey, 120)->block(5, $callback);
    }

    private function duplicateExists(User $user, SocialMediaPlatform $account, string $action, string $operationKey): bool
    {
        if (! Schema::hasTable('ext_social_media_distribution_audits')) {
            return false;
        }

        $window = max(60, (int) config('social-media.engagement.duplicate_window_seconds', 86400));
        $rows = DB::table('ext_social_media_distribution_audits')
            ->where('user_id', $user->getKey())
            ->where('social_media_platform_id', $account->getKey())
            ->where('destination', self::DESTINATION)
            ->where('action', $action)
            ->where('created_at', '>=', now()->subSeconds($window))
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        foreach ($rows as $row) {
            if (($this->decodeSnapshot($row->snapshot ?? null)['operation_key'] ?? null) === $operationKey) {
                return true;
            }
        }

        return false;
    }

    private function operationKey(SocialMediaPlatform $account, string $operation, string $engagementId, string $payloadHash): string
    {
        return hash('sha256', implode('|', [
            (string) $account->getKey(),
            $this->providerKey($account),
            $operation,
            $engagementId,
            $payloadHash,
        ]));
    }

    private function providerReceipt(string $provider, string $status, Response $response, string $operationKey): array
    {
        return [
            'status' => $status,
            'provider' => $provider,
            'operation_key' => $operationKey,
            'provider_response_id' => (string) (
                $response->json('id')
                ?? $response->json('data.id')
                ?? $response->json('commentUrn')
                ?? $response->header('x-restli-id')
                ?? ''
            ),
            'provider_status' => $response->status(),
            'completed_at' => now()->toIso8601String(),
        ];
    }

    private function audit(User $user, SocialMediaPlatform $account, string $action, array $snapshot): void
    {
        if (! Schema::hasTable('ext_social_media_distribution_audits')) {
            return;
        }

        DB::table('ext_social_media_distribution_audits')->insert([
            'user_id' => $user->getKey(),
            'social_media_platform_id' => $account->getKey(),
            'destination' => self::DESTINATION,
            'action' => $action,
            'snapshot' => json_encode($this->safeSnapshot($snapshot), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }

    private function safeSnapshot(array $snapshot): array
    {
        foreach ($snapshot as $key => $value) {
            if (preg_match('/token|secret|password|authorization/i', (string) $key)) {
                unset($snapshot[$key]);
                continue;
            }

            if (is_array($value)) {
                $snapshot[$key] = $this->safeSnapshot($value);
            }
        }

        return $snapshot;
    }

    private function decodeSnapshot(mixed $snapshot): array
    {
        if (is_array($snapshot)) {
            return $snapshot;
        }

        if (is_string($snapshot)) {
            return (array) json_decode($snapshot, true);
        }

        return [];
    }

    private function persistGrantedScopes(SocialMediaPlatform $account, array $scopes): void
    {
        $credentials = (array) $account->credentials;
        $credentials['authorized_scopes'] = array_values(array_unique($scopes));
        $account->update(['credentials' => $credentials]);
    }

    private function providerKey(SocialMediaPlatform $account): string
    {
        $provider = (string) $account->platform;

        return $provider === PlatformEnum::youtube_shorts->value ? 'youtube' : $provider;
    }

    private function youtube(SocialMediaPlatform $account): Youtube
    {
        $platform = PlatformEnum::tryFrom((string) $account->platform) ?? PlatformEnum::youtube;

        return new Youtube(
            $platform,
            null,
            $this->accessToken($account),
            (string) data_get($account->credentials, 'refresh_token', '')
        );
    }

    private function accessToken(SocialMediaPlatform $account): string
    {
        $token = trim((string) data_get($account->credentials, 'access_token', ''));

        if ($token === '') {
            throw new RuntimeException('The provider access token is unavailable.');
        }

        return $token;
    }

    private function assertAccount(User $user, SocialMediaPlatform $account): void
    {
        if ((int) $account->user_id !== (int) $user->getKey()) {
            throw new RuntimeException('The engagement account is outside the current tenant.');
        }

        if (! in_array((string) $account->platform, self::SUPPORTED_PROVIDERS, true)) {
            throw new RuntimeException('The connected account is not supported by Titan Reach engagement.');
        }

        if (! $account->isConnected()) {
            throw new RuntimeException('The engagement account is not connected.');
        }
    }

    private function requireExplicitApproval(array $input): void
    {
        if (($input['approved'] ?? false) !== true) {
            throw new RuntimeException('approved engagement action required');
        }
    }

    private function requiredString(array $input, string $key, int $max): string
    {
        $value = trim((string) ($input[$key] ?? ''));

        if ($value === '' || mb_strlen($value) > $max) {
            throw new InvalidArgumentException("A valid {$key} is required.");
        }

        return $value;
    }

    private function assertSuccessful(Response $response, string $message): void
    {
        if (! $response->successful()) {
            throw new RuntimeException($message . ' Provider status: ' . $response->status());
        }
    }
}
