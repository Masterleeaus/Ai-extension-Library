<?php

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DistributionCapabilityService
{
    public function __construct(
        private readonly SocialMediaChannelEntitlementService $entitlements
    ) {}

    public function forDestination(
        User $user,
        string $destination,
        ?SocialMediaPlatform $account = null,
        bool $audit = true
    ): array {
        $definition = config("social-media.distribution.destinations.{$destination}");

        if (! is_array($definition)) {
            $definition = $this->unsupportedDefinition();
        }

        $mode = (string) ($definition['mode'] ?? DistributionItem::MODE_EXPORT_ONLY);
        $declaredCapabilities = (array) ($definition['capabilities'] ?? []);
        $tenantAllowed = ! $account || (int) $account->user_id === (int) $user->getKey();
        $requiresAccount = in_array($mode, [DistributionItem::MODE_DIRECT, DistributionItem::MODE_PARTNER], true);
        $adapterAvailable = (bool) ($definition['adapter_available'] ?? false);
        $accountConnected = $account?->isConnected() ?? false;
        $withinAllowance = $account && $tenantAllowed
            ? $this->entitlements->canPublish($user, $account)
            : false;

        $available = $tenantAllowed
            && $adapterAvailable
            && (! $requiresAccount || ($accountConnected && $withinAllowance));

        $effectiveCapabilities = $available
            ? $declaredCapabilities
            : array_map(static fn () => false, $declaredCapabilities);

        $result = [
            'destination'            => $destination,
            'mode'                   => $mode,
            'available'              => $available,
            'reason'                 => $this->reason(
                $tenantAllowed,
                $adapterAvailable,
                $requiresAccount,
                $accountConnected,
                $withinAllowance
            ),
            'account_id'             => $tenantAllowed ? $account?->getKey() : null,
            'content_types'          => array_values((array) ($definition['content_types'] ?? [])),
            'required_fields'        => array_values((array) ($definition['required_fields'] ?? [])),
            'media_rules'            => (array) ($definition['media_rules'] ?? []),
            'declared_capabilities'  => $declaredCapabilities,
            'effective_capabilities' => $effectiveCapabilities,
            'approval_required'      => (bool) ($definition['approval_required'] ?? false),
        ];

        if ($audit) {
            $this->audit($user, $destination, $account, $result);
        }

        return $result;
    }

    public function matrixForUser(User $user, bool $audit = false): array
    {
        $destinations = (array) config('social-media.distribution.destinations', []);

        return collect($destinations)
            ->mapWithKeys(function (array $definition, string $destination) use ($user, $audit) {
                $platform = $definition['platform'] ?? null;
                $account = $platform
                    ? SocialMediaPlatform::query()
                        ->where('user_id', $user->getKey())
                        ->where('platform', $platform)
                        ->orderByDesc('expires_at')
                        ->first()
                    : null;

                return [
                    $destination => $this->forDestination($user, $destination, $account, $audit),
                ];
            })
            ->all();
    }

    private function reason(
        bool $tenantAllowed,
        bool $adapterAvailable,
        bool $requiresAccount,
        bool $accountConnected,
        bool $withinAllowance
    ): ?string {
        if (! $tenantAllowed) {
            return 'tenant_scope_denied';
        }

        if (! $adapterAvailable) {
            return 'adapter_unavailable';
        }

        if ($requiresAccount && ! $accountConnected) {
            return 'account_not_connected';
        }

        if ($requiresAccount && ! $withinAllowance) {
            return 'outside_channel_allowance';
        }

        return null;
    }

    private function audit(
        User $user,
        string $destination,
        ?SocialMediaPlatform $account,
        array $snapshot
    ): void {
        DB::table('ext_social_media_distribution_audits')->insert([
            'user_id'                  => $user->getKey(),
            'social_media_platform_id' => $account && (int) $account->user_id === (int) $user->getKey()
                ? $account->getKey()
                : null,
            'destination'              => $destination,
            'action'                   => 'capability_snapshot',
            'snapshot'                 => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'created_at'               => now(),
        ]);
    }

    private function unsupportedDefinition(): array
    {
        return [
            'mode'              => DistributionItem::MODE_EXPORT_ONLY,
            'adapter_available' => false,
            'content_types'     => [],
            'required_fields'   => [],
            'media_rules'       => [],
            'capabilities'      => [
                'publish'  => false,
                'schedule' => false,
                'edit'     => false,
                'delete'   => false,
                'analytics'=> false,
                'inbox'    => false,
                'pricing'  => false,
                'inventory'=> false,
            ],
            'approval_required' => false,
        ];
    }
}
