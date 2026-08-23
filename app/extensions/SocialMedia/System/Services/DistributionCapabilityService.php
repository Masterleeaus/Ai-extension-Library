<?php

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class DistributionCapabilityService
{
    public const SUITABILITY_PRIMARY = 'primary';

    public const SUITABILITY_SUPPORTED = 'supported';

    public const SUITABILITY_SPECIAL_CASE = 'special-case';

    public const SUITABILITY_NOT_APPLICABLE = 'not-applicable';

    private ?array $destinationCatalogueCache = null;

    private ?array $verticalCatalogueCache = null;

    private ?array $verticalProfilesCache = null;

    public function __construct(
        private readonly SocialMediaChannelEntitlementService $entitlements
    ) {}

    public function forDestination(
        User $user,
        string $destination,
        ?SocialMediaPlatform $account = null,
        bool $audit = true
    ): array {
        $destinations = $this->destinations();
        $destinationExists = array_key_exists($destination, $destinations);
        $definition = $destinations[$destination] ?? $this->unsupportedDefinition();
        $result = $this->capabilityForDefinition(
            $user,
            $destination,
            $definition,
            $destinationExists,
            $account
        );

        if ($audit) {
            $this->audit($user, $destination, $account, $result);
        }

        return $result;
    }

    public function forVerticalDestination(
        User $user,
        string $destination,
        string $contentType,
        ?string $vertical = null,
        ?string $subtype = null,
        ?SocialMediaPlatform $account = null,
        bool $audit = true
    ): array {
        $destinations = $this->destinations();
        $destinationExists = array_key_exists($destination, $destinations);
        $definition = $destinations[$destination] ?? $this->unsupportedDefinition();
        $base = $this->capabilityForDefinition(
            $user,
            $destination,
            $definition,
            $destinationExists,
            $account
        );
        $suitability = $this->suitabilityForDefinition(
            $vertical,
            $destination,
            $contentType,
            $subtype,
            $user,
            $destinationExists ? $definition : null
        );
        $available = $base['available'] && $suitability['available'];
        $result = [
            ...$base,
            'available' => $available,
            'reason' => $base['reason'] ?? $suitability['reason'],
            'content_type' => $contentType,
            'vertical' => $suitability['vertical'],
            'vertical_label' => $suitability['vertical_label'],
            'business_subtype' => $suitability['business_subtype'],
            'subtype_known' => $suitability['subtype_known'],
            'suitability' => $suitability['suitability'],
            'required_fields' => array_values(array_unique([
                ...$base['required_fields'],
                ...$suitability['required_fields'],
            ])),
            'media_guidance' => $suitability['media_guidance'],
            'calls_to_action' => $suitability['calls_to_action'],
            'handoff_targets' => $suitability['handoff_targets'],
            'compliance_warnings' => $suitability['compliance_warnings'],
            'profile_version' => $suitability['profile_version'],
            'profile_provenance' => $suitability['profile_provenance'],
            'vertical_context_id' => $suitability['vertical_context_id'],
            'vertical_context_hash' => $suitability['vertical_context_hash'],
            'effective_capabilities' => $available
                ? $base['effective_capabilities']
                : array_map(static fn () => false, $base['declared_capabilities']),
        ];

        if ($audit) {
            $this->audit(
                $user,
                $destination,
                $account,
                $result,
                'vertical_capability_snapshot'
            );
        }

        return $result;
    }

    public function suitabilityFor(
        ?string $vertical,
        string $destination,
        string $contentType,
        ?string $subtype = null,
        ?User $user = null
    ): array {
        $destinations = $this->destinations();

        return $this->suitabilityForDefinition(
            $vertical,
            $destination,
            $contentType,
            $subtype,
            $user,
            $destinations[$destination] ?? null
        );
    }

    public function resolveVerticalProfile(
        ?string $vertical = null,
        ?string $subtype = null,
        ?User $user = null
    ): array {
        $context = $this->resolveVerticalContext($vertical, $subtype, $user);
        $profiles = $this->verticalProfiles();
        $rawVertical = $this->normaliseSlug($context['vertical'] ?? null);
        $slug = $this->canonicalVerticalSlug($context['vertical'] ?? null, $profiles);
        $provenance = array_values((array) ($context['provenance'] ?? []));

        if (! $slug) {
            $profile = $this->genericProfile();
            $profile['resolved_subtype'] = $this->normaliseSlug($context['subtype'] ?? null);
            $profile['subtype_known'] = false;
            $profile['profile_version'] = $this->profileVersion();
            $profile['profile_provenance'] = array_values(array_unique([
                ...$provenance,
                'generic_fallback',
            ]));
            $profile['vertical_context_id'] = $context['context_id'] ?? null;
            $profile['vertical_context_hash'] = $context['context_hash'] ?? null;

            return $profile;
        }

        $profile = $profiles[$slug];
        $resolvedSubtype = $this->normaliseSlug($context['subtype'] ?? null)
            ?? $this->inferredSubtype($rawVertical, $profile);
        $profile['resolved_subtype'] = $resolvedSubtype;
        $profile['subtype_known'] = $resolvedSubtype
            ? in_array($resolvedSubtype, (array) ($profile['subtypes'] ?? []), true)
            : false;
        $profile['profile_version'] = $this->profileVersion();
        $profile['profile_provenance'] = array_values(array_unique([
            ...$provenance,
            'canonical_catalogue',
        ]));
        $profile['vertical_context_id'] = $context['context_id'] ?? null;
        $profile['vertical_context_hash'] = $context['context_hash'] ?? null;

        $tenantOverride = $this->tenantOverride($user, $slug);

        if ($tenantOverride !== []) {
            $profile = $this->applyTenantOverride($profile, $tenantOverride);
            $profile['profile_provenance'][] = 'tenant_override';
            $profile['profile_provenance'] = array_values(array_unique($profile['profile_provenance']));
        }

        return $profile;
    }

    public function canonicalVerticalSlugs(): array
    {
        return array_keys($this->verticalProfiles());
    }

    public function matrixForUser(User $user, bool $audit = false): array
    {
        return collect($this->destinations())
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

    private function capabilityForDefinition(
        User $user,
        string $destination,
        array $definition,
        bool $destinationExists,
        ?SocialMediaPlatform $account
    ): array {
        $mode = (string) ($definition['mode'] ?? DistributionItem::MODE_EXPORT_ONLY);
        $expectedPlatform = $definition['platform'] ?? null;
        $declaredCapabilities = (array) ($definition['capabilities'] ?? []);
        $tenantAllowed = ! $account || (int) $account->user_id === (int) $user->getKey();
        $accountMatchesDestination = ! $expectedPlatform
            || ($account && (string) $account->platform === (string) $expectedPlatform);
        $requiresAccount = in_array($mode, [DistributionItem::MODE_DIRECT, DistributionItem::MODE_PARTNER], true);
        $adapterAvailable = $destinationExists && (bool) ($definition['adapter_available'] ?? false);
        $accountConnected = $account?->isConnected() ?? false;
        $withinAllowance = $account && $tenantAllowed && $accountMatchesDestination
            ? $this->entitlements->canPublish($user, $account)
            : false;

        $available = $tenantAllowed
            && $accountMatchesDestination
            && $adapterAvailable
            && (! $requiresAccount || ($accountConnected && $withinAllowance));

        return [
            'destination' => $destination,
            'mode' => $mode,
            'available' => $available,
            'reason' => $destinationExists
                ? $this->reason(
                    $tenantAllowed,
                    $accountMatchesDestination,
                    $adapterAvailable,
                    $requiresAccount,
                    $accountConnected,
                    $withinAllowance
                )
                : 'destination_unknown',
            'account_id' => $tenantAllowed ? $account?->getKey() : null,
            'content_types' => array_values((array) ($definition['content_types'] ?? [])),
            'required_fields' => array_values((array) ($definition['required_fields'] ?? [])),
            'media_rules' => (array) ($definition['media_rules'] ?? []),
            'declared_capabilities' => $declaredCapabilities,
            'effective_capabilities' => $available
                ? $declaredCapabilities
                : array_map(static fn () => false, $declaredCapabilities),
            'approval_required' => (bool) ($definition['approval_required'] ?? false),
        ];
    }

    private function suitabilityForDefinition(
        ?string $vertical,
        string $destination,
        string $contentType,
        ?string $subtype,
        ?User $user,
        ?array $definition
    ): array {
        $profile = $this->resolveVerticalProfile($vertical, $subtype, $user);
        $suitability = $this->normaliseSuitability((string) data_get(
            $profile,
            "destination_suitability.{$destination}",
            self::SUITABILITY_NOT_APPLICABLE
        ));
        $validContentType = in_array($contentType, DistributionItem::contentTypes(), true);
        $profileSupportsContent = in_array(
            $contentType,
            (array) ($profile['content_types'] ?? []),
            true
        );
        $destinationSupportsContent = $definition
            && $this->destinationSupportsContent($definition, $contentType);
        $available = $validContentType
            && $profileSupportsContent
            && $destinationSupportsContent
            && $suitability !== self::SUITABILITY_NOT_APPLICABLE;

        return [
            'vertical' => (string) ($profile['slug'] ?? 'generic-business'),
            'vertical_label' => (string) ($profile['label'] ?? 'Generic Business'),
            'business_subtype' => $profile['resolved_subtype'] ?? null,
            'subtype_known' => (bool) ($profile['subtype_known'] ?? false),
            'destination' => $destination,
            'content_type' => $contentType,
            'suitability' => $suitability,
            'available' => $available,
            'reason' => $this->suitabilityReason(
                $validContentType,
                $profileSupportsContent,
                (bool) $definition,
                (bool) $destinationSupportsContent,
                $suitability
            ),
            'required_fields' => array_values((array) ($profile['required_fields'] ?? [])),
            'media_guidance' => array_values((array) ($profile['media_guidance'] ?? [])),
            'calls_to_action' => array_values((array) ($profile['calls_to_action'] ?? [])),
            'handoff_targets' => (array) ($profile['handoff_targets'] ?? []),
            'compliance_warnings' => array_values((array) ($profile['compliance_warnings'] ?? [])),
            'profile_version' => (string) ($profile['profile_version'] ?? $this->profileVersion()),
            'profile_provenance' => array_values((array) ($profile['profile_provenance'] ?? [])),
            'vertical_context_id' => $profile['vertical_context_id'] ?? null,
            'vertical_context_hash' => $profile['vertical_context_hash'] ?? null,
        ];
    }

    private function destinations(): array
    {
        if ($this->destinationCatalogueCache !== null) {
            return $this->destinationCatalogueCache;
        }

        $catalogue = require dirname(__DIR__, 2) . '/config/distribution.php';
        $baseDestinations = (array) ($catalogue['destinations'] ?? []);
        $configuredDestinations = (array) config('social-media.distribution.destinations', []);

        return $this->destinationCatalogueCache = array_replace_recursive(
            $baseDestinations,
            $configuredDestinations
        );
    }

    private function verticalCatalogue(): array
    {
        if ($this->verticalCatalogueCache !== null) {
            return $this->verticalCatalogueCache;
        }

        $catalogue = require dirname(__DIR__, 2) . '/config/vertical-distribution.php';
        $configured = (array) config('social-media.vertical_distribution', []);

        return $this->verticalCatalogueCache = array_replace_recursive(
            $catalogue,
            Arr::except($configured, ['verticals', 'tenant_overrides'])
        );
    }

    private function verticalProfiles(): array
    {
        if ($this->verticalProfilesCache !== null) {
            return $this->verticalProfilesCache;
        }

        $catalogue = require dirname(__DIR__, 2) . '/config/vertical-distribution.php';
        $baseProfiles = (array) ($catalogue['verticals'] ?? []);
        $configuredProfiles = (array) config('social-media.vertical_distribution.verticals', []);
        $overrides = array_intersect_key($configuredProfiles, $baseProfiles);

        return $this->verticalProfilesCache = array_replace_recursive($baseProfiles, $overrides);
    }

    private function genericProfile(): array
    {
        $catalogue = $this->verticalCatalogue();
        $configured = (array) config('social-media.vertical_distribution.generic_profile', []);
        $profile = array_replace_recursive(
            (array) ($catalogue['generic_profile'] ?? []),
            $configured
        );
        $profile['content_types'] = DistributionItem::contentTypes();

        return $profile;
    }

    private function profileVersion(): string
    {
        return (string) ($this->verticalCatalogue()['profile_version'] ?? 'unversioned');
    }

    private function resolveVerticalContext(
        ?string $vertical,
        ?string $subtype,
        ?User $user
    ): array {
        if ($vertical !== null && trim($vertical) !== '') {
            return [
                'vertical' => $vertical,
                'subtype' => $subtype,
                'context_id' => null,
                'context_hash' => null,
                'provenance' => ['explicit_context'],
            ];
        }

        $resolver = config(
            'social-media.vertical_distribution.vertical_context_resolver',
            $this->verticalCatalogue()['vertical_context_resolver'] ?? null
        );

        if ($user && is_string($resolver) && $resolver !== ''
            && (class_exists($resolver) || interface_exists($resolver) || app()->bound($resolver))) {
            try {
                $instance = app($resolver);

                if (method_exists($instance, 'resolve')) {
                    $resolved = $instance->resolve($user);

                    if (is_object($resolved) && method_exists($resolved, 'toArray')) {
                        $resolved = $resolved->toArray();
                    }

                    $resolved = (array) $resolved;

                    return [
                        'vertical' => $resolved['vertical']
                            ?? $resolved['vertical_slug']
                            ?? data_get($resolved, 'resolved.capabilities.vertical_family'),
                        'subtype' => $resolved['subtype']
                            ?? $resolved['business_subtype']
                            ?? data_get($resolved, 'resolved.capabilities.subtype')
                            ?? $subtype,
                        'context_id' => $resolved['context_id'] ?? null,
                        'context_hash' => $resolved['context_hash'] ?? null,
                        'provenance' => ['vertical_context_resolver'],
                    ];
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return [
            'vertical' => null,
            'subtype' => $subtype,
            'context_id' => null,
            'context_hash' => null,
            'provenance' => ['generic_context'],
        ];
    }

    private function canonicalVerticalSlug(?string $vertical, array $profiles): ?string
    {
        $slug = $this->normaliseSlug($vertical);

        if (! $slug) {
            return null;
        }

        $slug = $this->verticalAliases()[$slug] ?? $slug;

        if (array_key_exists($slug, $profiles)) {
            return $slug;
        }

        foreach ($profiles as $profileSlug => $profile) {
            if (in_array($slug, (array) ($profile['subtypes'] ?? []), true)) {
                return $profileSlug;
            }
        }

        return null;
    }

    private function verticalAliases(): array
    {
        return [
            'field-and-home-services' => 'field-home-services',
            'facilities-management' => 'field-home-services',
            'facilities-maintenance' => 'field-home-services',
            'bnb-hotel-and-rooming-services' => 'accommodation',
            'bnb-hotel-rooming-services' => 'accommodation',
            'hotel-bnb-rooming-services' => 'accommodation',
            'salons-and-personal-care' => 'salons-personal-care',
            'fitness-and-membership-businesses' => 'fitness-membership',
            'automotive' => 'automotive-services',
            'e-commerce-and-retail' => 'ecommerce-retail',
            'e-commerce-retail' => 'ecommerce-retail',
            'hire-and-rental' => 'hire-rental',
            'booking-reservation-and-capacity-based-businesses' => 'booking-capacity',
            'booking-reservation-capacity-businesses' => 'booking-capacity',
        ];
    }

    private function inferredSubtype(?string $rawVertical, array $profile): ?string
    {
        if (! $rawVertical) {
            return null;
        }

        $candidate = $this->subtypeAliases()[$rawVertical] ?? $rawVertical;

        return in_array($candidate, (array) ($profile['subtypes'] ?? []), true)
            ? $candidate
            : null;
    }

    private function subtypeAliases(): array
    {
        return [
            'facilities-management' => 'facilities-maintenance',
        ];
    }

    private function tenantOverride(?User $user, string $slug): array
    {
        if (! $user) {
            return [];
        }

        $override = (array) data_get(
            config('social-media.vertical_distribution.tenant_overrides', []),
            $user->getKey() . '.' . $slug,
            []
        );

        return Arr::only($override, [
            'content_types',
            'destination_suitability',
            'required_fields',
            'media_guidance',
            'calls_to_action',
            'handoff_targets',
            'compliance_warnings',
        ]);
    }

    private function applyTenantOverride(array $profile, array $override): array
    {
        if (array_key_exists('content_types', $override)) {
            $profile['content_types'] = array_values(array_intersect(
                (array) ($profile['content_types'] ?? []),
                (array) $override['content_types']
            ));
        }

        foreach ((array) ($override['destination_suitability'] ?? []) as $destination => $requested) {
            if (! array_key_exists($destination, (array) ($profile['destination_suitability'] ?? []))) {
                continue;
            }

            $current = $this->normaliseSuitability(
                (string) $profile['destination_suitability'][$destination]
            );
            $requested = $this->normaliseSuitability((string) $requested);

            if ($current === self::SUITABILITY_NOT_APPLICABLE
                && $requested !== self::SUITABILITY_NOT_APPLICABLE) {
                continue;
            }

            $profile['destination_suitability'][$destination] = $requested;
        }

        foreach (['required_fields', 'media_guidance', 'calls_to_action', 'compliance_warnings'] as $field) {
            if (! array_key_exists($field, $override)) {
                continue;
            }

            $profile[$field] = array_values(array_unique([
                ...(array) ($profile[$field] ?? []),
                ...(array) $override[$field],
            ]));
        }

        foreach ((array) ($override['handoff_targets'] ?? []) as $handoff => $targets) {
            $targets = array_values(array_intersect(
                (array) $targets,
                $this->allowedHandoffTargets()
            ));

            if ($targets === []) {
                continue;
            }

            $profile['handoff_targets'][$handoff] = array_values(array_unique([
                ...(array) data_get($profile, "handoff_targets.{$handoff}", []),
                ...$targets,
            ]));
        }

        return $profile;
    }

    private function allowedHandoffTargets(): array
    {
        return [
            'crm',
            'workcore',
            'commerce',
            'bookings',
            'property',
            'automotive',
            'hire',
            'memberships',
            'marketing',
        ];
    }

    private function destinationSupportsContent(array $definition, string $contentType): bool
    {
        $declared = (array) ($definition['content_types'] ?? []);

        if (in_array($contentType, $declared, true)) {
            return true;
        }

        foreach ($this->providerContentAliases()[$contentType] ?? [] as $alias) {
            if (in_array($alias, $declared, true)) {
                return true;
            }
        }

        return false;
    }

    private function providerContentAliases(): array
    {
        return [
            DistributionItem::TYPE_ROOM_STAY_OFFER => [
                DistributionItem::TYPE_PRODUCT_OFFER,
                DistributionItem::TYPE_SERVICE_PROMOTION,
                DistributionItem::TYPE_PROPERTY_LISTING,
            ],
            DistributionItem::TYPE_MEMBERSHIP_OFFER => [
                DistributionItem::TYPE_PRODUCT_OFFER,
                DistributionItem::TYPE_SERVICE_PROMOTION,
            ],
            DistributionItem::TYPE_CLASS_SESSION_OFFER => [
                DistributionItem::TYPE_EVENT,
                DistributionItem::TYPE_SERVICE_PROMOTION,
            ],
            DistributionItem::TYPE_BOOKING_OFFER => [
                DistributionItem::TYPE_EVENT,
                DistributionItem::TYPE_SERVICE_PROMOTION,
                DistributionItem::TYPE_PRODUCT_OFFER,
            ],
            DistributionItem::TYPE_HIRE_RENTAL_LISTING => [
                DistributionItem::TYPE_MARKETPLACE_LISTING,
                DistributionItem::TYPE_PRODUCT_OFFER,
                DistributionItem::TYPE_CLASSIFIED_LISTING,
            ],
        ];
    }

    private function normaliseSuitability(string $value): string
    {
        return in_array($value, [
            self::SUITABILITY_PRIMARY,
            self::SUITABILITY_SUPPORTED,
            self::SUITABILITY_SPECIAL_CASE,
            self::SUITABILITY_NOT_APPLICABLE,
        ], true) ? $value : self::SUITABILITY_NOT_APPLICABLE;
    }

    private function suitabilityReason(
        bool $validContentType,
        bool $profileSupportsContent,
        bool $destinationExists,
        bool $destinationSupportsContent,
        string $suitability
    ): ?string {
        if (! $validContentType) {
            return 'unsupported_content_type';
        }

        if (! $profileSupportsContent) {
            return 'content_not_supported_by_vertical';
        }

        if (! $destinationExists) {
            return 'destination_unknown';
        }

        if (! $destinationSupportsContent) {
            return 'content_not_supported_by_destination';
        }

        if ($suitability === self::SUITABILITY_NOT_APPLICABLE) {
            return 'destination_not_applicable_to_vertical';
        }

        return null;
    }

    private function normaliseSlug(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Str::slug($value);
    }

    private function reason(
        bool $tenantAllowed,
        bool $accountMatchesDestination,
        bool $adapterAvailable,
        bool $requiresAccount,
        bool $accountConnected,
        bool $withinAllowance
    ): ?string {
        if (! $tenantAllowed) {
            return 'tenant_scope_denied';
        }

        if (! $accountMatchesDestination) {
            return 'account_destination_mismatch';
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
        array $snapshot,
        string $action = 'capability_snapshot'
    ): void {
        if (! Schema::hasTable('ext_social_media_distribution_audits')) {
            return;
        }

        DB::table('ext_social_media_distribution_audits')->insert([
            'user_id'                  => $user->getKey(),
            'social_media_platform_id' => $account && (int) $account->user_id === (int) $user->getKey()
                ? $account->getKey()
                : null,
            'destination'              => $destination,
            'action'                   => $action,
            'snapshot'                 => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'created_at'               => now(),
        ]);
    }

    private function unsupportedDefinition(): array
    {
        return [
            'mode'              => DistributionItem::MODE_EXPORT_ONLY,
            'adapter_available' => false,
            'platform'          => null,
            'content_types'     => [],
            'required_fields'   => [],
            'media_rules'       => [],
            'capabilities'      => [
                'publish'   => false,
                'schedule'  => false,
                'edit'      => false,
                'delete'    => false,
                'analytics' => false,
                'inbox'     => false,
                'pricing'   => false,
                'inventory' => false,
            ],
            'approval_required' => false,
        ];
    }
}
