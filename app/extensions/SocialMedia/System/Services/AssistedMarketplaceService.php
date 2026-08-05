<?php

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AssistedMarketplaceService
{
    private ?array $operationalConfigCache = null;

    public function __construct(
        private readonly DistributionCapabilityService $capabilities
    ) {}

    public function preparePackage(
        User $user,
        DistributionItem $item,
        string $destination,
        array $input,
        string $idempotencyKey
    ): array {
        [$definition, $capability] = $this->assertContext(
            $user,
            $item,
            $destination,
            $this->verticalFrom($item, $input),
            $this->subtypeFrom($item, $input),
            true
        );
        $package = $this->validatedPackage($item, $definition, $capability, $input);
        $requestHash = $this->requestHash([
            'destination' => $destination,
            'distribution_item_id' => $item->getKey(),
            'package' => $package,
        ]);

        if ($cached = $this->cachedOperation(
            $item,
            $destination,
            'prepare',
            $idempotencyKey,
            $requestHash
        )) {
            return $cached;
        }

        $preparedAt = now();
        $expiresAt = $preparedAt->copy()->addDays($this->defaultExpiryDays());
        $packageHash = $this->requestHash($package);
        $result = [
            'destination' => $destination,
            'destination_label' => (string) ($definition['label'] ?? $destination),
            'status' => 'ready_for_manual_post',
            'official_posting_url' => (string) $definition['official_posting_url'],
            'package_hash' => $packageHash,
            'prepared_at' => $preparedAt->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'renewal_due_at' => $expiresAt->toIso8601String(),
            'package' => $package,
            'export_manifest' => $this->exportManifest($package, $definition),
            'vertical' => $capability['vertical'],
            'business_subtype' => $capability['business_subtype'],
            'profile_version' => $capability['profile_version'],
            'profile_provenance' => $capability['profile_provenance'],
            'vertical_context_id' => $capability['vertical_context_id'] ?? null,
            'vertical_context_hash' => $capability['vertical_context_hash'] ?? null,
            'manual_action_required' => true,
            'direct_publish_performed' => false,
        ];

        $this->persistState($item, $destination, $result);
        $this->persistOperation(
            $item,
            $destination,
            'prepare',
            $idempotencyKey,
            $requestHash,
            $result
        );
        $this->audit($user, $destination, 'assisted_package_prepared', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'capability' => [
                'vertical' => $capability['vertical'],
                'business_subtype' => $capability['business_subtype'],
                'suitability' => $capability['suitability'],
                'profile_version' => $capability['profile_version'],
                'profile_provenance' => $capability['profile_provenance'],
            ],
            'result' => $this->auditResult($result),
        ]);

        return $result;
    }

    public function openOfficialDestination(
        User $user,
        DistributionItem $item,
        string $destination,
        string $idempotencyKey
    ): array {
        $this->assertOwner($user, $item);
        $state = $this->requiredState($item, $destination);
        [$definition] = $this->assertContext(
            $user,
            $item,
            $destination,
            $state['vertical'] ?? null,
            $state['business_subtype'] ?? null,
            true
        );

        if ($this->stateExpired($state)) {
            $this->persistExpiredState($item, $destination, $state);
            throw new RuntimeException('The prepared marketplace package has expired and must be reviewed again.');
        }

        $requestHash = $this->requestHash([
            'destination' => $destination,
            'package_hash' => $state['package_hash'],
            'operation' => 'open',
        ]);

        if ($cached = $this->cachedOperation(
            $item,
            $destination,
            'open',
            $idempotencyKey,
            $requestHash
        )) {
            return $cached;
        }

        $result = [
            'destination' => $destination,
            'status' => 'opened_official_destination',
            'official_posting_url' => (string) $definition['official_posting_url'],
            'package_hash' => (string) $state['package_hash'],
            'opened_at' => now()->toIso8601String(),
            'manual_action_required' => true,
            'direct_publish_performed' => false,
        ];

        $this->persistState($item, $destination, [...$state, ...$result]);
        $this->persistOperation(
            $item,
            $destination,
            'open',
            $idempotencyKey,
            $requestHash,
            $result
        );
        $this->audit($user, $destination, 'assisted_destination_opened', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'result' => $result,
        ]);

        return $result;
    }

    public function markCompleted(
        User $user,
        DistributionItem $item,
        string $destination,
        bool $humanConfirmed,
        string $externalUrl,
        string $idempotencyKey,
        ?string $externalListingId = null
    ): array {
        $this->assertOwner($user, $item);
        $state = $this->requiredState($item, $destination);
        [$definition] = $this->assertContext(
            $user,
            $item,
            $destination,
            $state['vertical'] ?? null,
            $state['business_subtype'] ?? null,
            true
        );

        if (! $humanConfirmed) {
            throw new RuntimeException('Explicit human confirmation is required after posting on the marketplace.');
        }

        $externalUrl = $this->validatedExternalUrl($definition, $externalUrl);
        $externalListingId = $this->stringOrNull($externalListingId);
        $requestHash = $this->requestHash([
            'destination' => $destination,
            'package_hash' => $state['package_hash'],
            'external_url' => $externalUrl,
            'external_listing_id' => $externalListingId,
            'human_confirmed' => true,
        ]);

        if ($cached = $this->cachedOperation(
            $item,
            $destination,
            'complete',
            $idempotencyKey,
            $requestHash
        )) {
            return $cached;
        }

        if ($this->stateExpired($state)) {
            $this->persistExpiredState($item, $destination, $state);
            throw new RuntimeException('The prepared marketplace package has expired and must be reviewed again.');
        }

        $result = [
            'destination' => $destination,
            'status' => 'manually_published',
            'external_url' => $externalUrl,
            'external_listing_id' => $externalListingId,
            'package_hash' => (string) $state['package_hash'],
            'human_confirmed' => true,
            'confirmed_by' => $user->getKey(),
            'confirmed_at' => now()->toIso8601String(),
            'renewal_due_at' => now()->addDays($this->defaultExpiryDays())->toIso8601String(),
            'manual_action_required' => false,
            'direct_publish_performed' => false,
        ];

        $this->persistState($item, $destination, [...$state, ...$result]);
        $this->persistOperation(
            $item,
            $destination,
            'complete',
            $idempotencyKey,
            $requestHash,
            $result
        );
        $this->audit($user, $destination, 'assisted_listing_manually_confirmed', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'result' => $result,
        ]);

        return $result;
    }

    public function prepareRenewal(
        User $user,
        DistributionItem $item,
        string $destination,
        string $idempotencyKey
    ): array {
        $this->assertOwner($user, $item);
        $state = $this->requiredState($item, $destination);
        [$definition] = $this->assertContext(
            $user,
            $item,
            $destination,
            $state['vertical'] ?? null,
            $state['business_subtype'] ?? null,
            true
        );
        $requestHash = $this->requestHash([
            'destination' => $destination,
            'package_hash' => $state['package_hash'],
            'operation' => 'renew',
        ]);

        if ($cached = $this->cachedOperation(
            $item,
            $destination,
            'renew',
            $idempotencyKey,
            $requestHash
        )) {
            return $cached;
        }

        if ($this->stateExpired($state)
            && (string) ($state['status'] ?? '') !== 'manually_published') {
            $state = $this->persistExpiredState($item, $destination, $state);
        }

        if (! in_array((string) ($state['status'] ?? ''), [
            'manually_published',
            'expired',
        ], true)) {
            throw new RuntimeException('The assisted listing must be manually published or expired before renewal preparation.');
        }

        $renewalCount = max(0, (int) ($state['renewal_count'] ?? 0)) + 1;
        $renewalDueAt = now()->addDays($this->defaultExpiryDays());
        $result = [
            'destination' => $destination,
            'status' => 'renewal_ready_for_manual_post',
            'renewal_count' => $renewalCount,
            'renewal_prepared_at' => now()->toIso8601String(),
            'expires_at' => $renewalDueAt->toIso8601String(),
            'renewal_due_at' => $renewalDueAt->toIso8601String(),
            'official_posting_url' => (string) $definition['official_posting_url'],
            'package_hash' => (string) $state['package_hash'],
            'package' => (array) ($state['package'] ?? []),
            'export_manifest' => (array) ($state['export_manifest'] ?? []),
            'previous_external_url' => $state['external_url'] ?? null,
            'external_url' => null,
            'external_listing_id' => null,
            'requires_copy_review' => true,
            'manual_action_required' => true,
            'direct_publish_performed' => false,
        ];

        $this->persistState($item, $destination, [...$state, ...$result]);
        $this->persistOperation(
            $item,
            $destination,
            'renew',
            $idempotencyKey,
            $requestHash,
            $result
        );
        $this->audit($user, $destination, 'assisted_renewal_prepared', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'result' => $this->auditResult($result),
        ]);

        return $result;
    }

    public function enquiryHandoff(
        User $user,
        DistributionItem $item,
        string $destination,
        array $enquiry
    ): array {
        $this->assertOwner($user, $item);
        $state = $this->requiredState($item, $destination);
        $this->assertContext(
            $user,
            $item,
            $destination,
            $state['vertical'] ?? null,
            $state['business_subtype'] ?? null,
            false
        );

        if (! in_array((string) ($state['status'] ?? ''), [
            'manually_published',
            'renewal_ready_for_manual_post',
        ], true)) {
            throw new RuntimeException('Marketplace enquiries can be recorded only after manual publication.');
        }

        $enquiryId = trim((string) ($enquiry['enquiry_id'] ?? ''));
        $message = trim((string) ($enquiry['message'] ?? ''));

        if ($enquiryId === '' || $message === '') {
            throw new InvalidArgumentException('Marketplace enquiry ID and message are required.');
        }

        $handoffs = array_values((array) ($state['enquiry_handoffs'] ?? []));

        foreach ($handoffs as $existing) {
            if (is_array($existing) && (string) ($existing['enquiry_id'] ?? '') === $enquiryId) {
                return $existing;
            }
        }

        $handoff = [
            'enquiry_id' => $enquiryId,
            'buyer_alias' => trim((string) ($enquiry['buyer_alias'] ?? '')),
            'subject' => trim((string) ($enquiry['subject'] ?? '')),
            'message' => $message,
            'received_at' => (string) ($enquiry['received_at'] ?? now()->toIso8601String()),
            'status' => 'human_handoff_required',
            'handoff_targets' => (array) data_get($state, 'package.vertical_context.handoff_targets.enquiry', []),
            'automated_reply_sent' => false,
        ];
        $handoffs[] = $handoff;
        $state['enquiry_handoffs'] = array_slice($handoffs, -100);
        $this->persistState($item, $destination, $state);
        $this->audit($user, $destination, 'assisted_enquiry_handoff', [
            'distribution_item_id' => $item->getKey(),
            'handoff' => $handoff,
        ]);

        return $handoff;
    }

    public function status(User $user, DistributionItem $item, string $destination): array
    {
        $this->assertOwner($user, $item);
        $state = $this->requiredState($item, $destination);
        $this->assertContext(
            $user,
            $item,
            $destination,
            $state['vertical'] ?? null,
            $state['business_subtype'] ?? null,
            false
        );

        if ($this->stateExpired($state)
            && ! in_array((string) ($state['status'] ?? ''), ['manually_published', 'expired'], true)) {
            $state = $this->persistExpiredState($item, $destination, $state);
        }

        return $state;
    }

    private function assertContext(
        User $user,
        DistributionItem $item,
        string $destination,
        ?string $vertical,
        ?string $subtype,
        bool $requiresApproval
    ): array {
        $this->assertOwner($user, $item);
        $definition = $this->operationalDefinition($destination);
        $capability = $this->capabilities->forVerticalDestination(
            $user,
            $destination,
            $item->content_type,
            $vertical,
            $subtype,
            null,
            false
        );

        if ($capability['mode'] !== DistributionItem::MODE_ASSISTED) {
            throw new RuntimeException('The selected destination is not an assisted marketplace.');
        }

        if (! $capability['available']) {
            $reason = (string) ($capability['reason'] ?? 'assisted_destination_unavailable');

            if ($reason === 'destination_not_applicable_to_vertical') {
                throw new RuntimeException('destination_not_applicable_to_vertical: the listing does not fit this vertical.');
            }

            throw new RuntimeException('The assisted marketplace destination is unavailable: ' . $reason);
        }

        if ($requiresApproval && $item->approval_status !== 'approved') {
            throw new RuntimeException('The marketplace listing requires approval before assisted distribution.');
        }

        return [$definition, $capability];
    }

    private function assertOwner(User $user, DistributionItem $item): void
    {
        if ((int) $item->user_id !== (int) $user->getKey()) {
            throw new RuntimeException('The assisted marketplace listing is outside the current tenant.');
        }
    }

    private function validatedPackage(
        DistributionItem $item,
        array $definition,
        array $capability,
        array $input
    ): array {
        foreach (['title', 'description', 'category', 'price_minor', 'currency', 'location', 'image_urls'] as $field) {
            $value = data_get($input, $field);

            if ($value === null || $value === '' || $value === []) {
                throw new InvalidArgumentException("Missing required marketplace listing field: {$field}.");
            }
        }

        $priceMinor = data_get($input, 'price_minor');

        if (! is_int($priceMinor) || $priceMinor < 0) {
            throw new InvalidArgumentException('Marketplace price must be a non-negative integer in minor currency units.');
        }

        $currency = strtoupper(trim((string) data_get($input, 'currency')));

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException('Marketplace currency must be a three-letter currency code.');
        }

        $imageUrls = array_values(array_unique(array_filter(
            (array) data_get($input, 'image_urls', []),
            static fn ($url) => is_string($url) && trim($url) !== ''
        )));

        if ($imageUrls === [] || count($imageUrls) > $this->maxImages()) {
            throw new InvalidArgumentException('Marketplace image count is invalid.');
        }

        foreach ($imageUrls as $imageUrl) {
            if (! filter_var($imageUrl, FILTER_VALIDATE_URL)
                || strtolower((string) parse_url($imageUrl, PHP_URL_SCHEME)) !== 'https') {
                throw new InvalidArgumentException('Every marketplace image URL must be a valid HTTPS URL.');
            }
        }

        $condition = trim((string) data_get($input, 'condition', ''));

        if ($condition === '' && $this->requiresCondition($item->content_type)) {
            throw new InvalidArgumentException('Marketplace condition is required for tangible listings.');
        }

        if ($condition === '') {
            $condition = 'not_applicable';
        }

        $callToAction = trim((string) data_get($input, 'call_to_action', ''));

        if ($callToAction === '') {
            $callToAction = (string) data_get($capability, 'calls_to_action.0', 'Contact us');
        }

        $verticalFields = (array) data_get($input, 'vertical_fields', []);
        $baseAliases = [
            'title' => data_get($input, 'title'),
            'content' => data_get($input, 'description'),
            'description' => data_get($input, 'description'),
            'category' => data_get($input, 'category'),
            'condition' => $condition,
            'price' => $priceMinor,
            'images' => $imageUrls,
            'media' => $imageUrls,
            'location' => data_get($input, 'location'),
            'call_to_action' => $callToAction,
        ];

        foreach ((array) ($capability['required_fields'] ?? []) as $field) {
            $value = $baseAliases[$field] ?? data_get($verticalFields, $field);

            if ($value === null || $value === '' || $value === []) {
                throw new InvalidArgumentException("Missing required vertical listing field: {$field}.");
            }
        }

        $responseTemplates = array_values(array_filter(
            (array) data_get($input, 'response_templates', []),
            static fn ($template) => is_string($template) && trim($template) !== ''
        ));

        if ($responseTemplates === []) {
            $responseTemplates = [
                'Yes, this listing is still available.',
                'Please confirm your preferred collection, delivery, appointment or booking time.',
                'The price, condition and terms are as shown in the listing package.',
            ];
        }

        $package = [
            'content_type' => $item->content_type,
            'title' => trim((string) data_get($input, 'title')),
            'description' => trim((string) data_get($input, 'description')),
            'category' => trim((string) data_get($input, 'category')),
            'condition' => $condition,
            'price_minor' => $priceMinor,
            'currency' => $currency,
            'location' => trim((string) data_get($input, 'location')),
            'image_urls' => $imageUrls,
            'image_overlays' => array_values((array) data_get($input, 'image_overlays', [])),
            'attributes' => (array) data_get($input, 'attributes', []),
            'vertical_fields' => $verticalFields,
            'delivery_details' => (array) data_get($input, 'delivery_details', []),
            'contact_preferences' => (array) data_get($input, 'contact_preferences', []),
            'call_to_action' => $callToAction,
            'response_templates' => $responseTemplates,
            'posting_guidance' => array_values((array) ($definition['posting_guidance'] ?? [])),
            'vertical_context' => [
                'vertical' => $capability['vertical'],
                'vertical_label' => $capability['vertical_label'],
                'business_subtype' => $capability['business_subtype'],
                'subtype_known' => $capability['subtype_known'],
                'suitability' => $capability['suitability'],
                'required_fields' => $capability['required_fields'],
                'media_guidance' => $capability['media_guidance'],
                'calls_to_action' => $capability['calls_to_action'],
                'handoff_targets' => $capability['handoff_targets'],
                'compliance_warnings' => $capability['compliance_warnings'],
                'profile_version' => $capability['profile_version'],
                'profile_provenance' => $capability['profile_provenance'],
                'vertical_context_id' => $capability['vertical_context_id'] ?? null,
                'vertical_context_hash' => $capability['vertical_context_hash'] ?? null,
            ],
            'source_reference' => [
                'distribution_item_id' => $item->getKey(),
                'source_type' => $item->source_type,
                'source_id' => $item->source_id,
            ],
        ];

        $this->assertPackageSize($package);

        return $package;
    }

    private function exportManifest(array $package, array $definition): array
    {
        return [
            'destination_label' => (string) ($definition['label'] ?? ''),
            'posting_mode' => 'assisted',
            'copy_fields' => [
                'title' => $package['title'],
                'description' => $package['description'],
                'category' => $package['category'],
                'condition' => $package['condition'],
                'price_minor' => $package['price_minor'],
                'currency' => $package['currency'],
                'location' => $package['location'],
                'call_to_action' => $package['call_to_action'],
                'vertical_fields' => $package['vertical_fields'],
            ],
            'ordered_image_urls' => $package['image_urls'],
            'image_overlays' => $package['image_overlays'],
            'response_templates' => $package['response_templates'],
            'media_guidance' => data_get($package, 'vertical_context.media_guidance', []),
            'compliance_warnings' => data_get($package, 'vertical_context.compliance_warnings', []),
            'posting_guidance' => $package['posting_guidance'],
            'instructions' => [
                'Open the official marketplace posting page.',
                'Copy the prepared fields and upload the ordered images.',
                'Review all vertical-specific fields, warnings and current availability before submitting.',
                'Submit the listing yourself on the marketplace.',
                'Return to Titan Reach and confirm completion with the external listing URL.',
            ],
        ];
    }

    private function operationalConfig(): array
    {
        if ($this->operationalConfigCache !== null) {
            return $this->operationalConfigCache;
        }

        $catalogue = require dirname(__DIR__, 2) . '/config/assisted-marketplaces.php';
        $configured = (array) config('social-media.assisted_marketplaces', []);

        return $this->operationalConfigCache = array_replace_recursive($catalogue, $configured);
    }

    private function operationalDefinition(string $destination): array
    {
        $definition = data_get($this->operationalConfig(), "destinations.{$destination}");

        if (! is_array($definition)
            || empty($definition['official_posting_url'])
            || empty($definition['allowed_external_hosts'])) {
            throw new InvalidArgumentException('Unsupported assisted marketplace destination.');
        }

        return $definition;
    }

    private function defaultExpiryDays(): int
    {
        return max(1, (int) ($this->operationalConfig()['default_expiry_days'] ?? 30));
    }

    private function maxImages(): int
    {
        return max(1, (int) ($this->operationalConfig()['max_images'] ?? 20));
    }

    private function maxPackageBytes(): int
    {
        return max(1024, (int) ($this->operationalConfig()['max_package_bytes'] ?? 262144));
    }

    private function idempotencyHistoryLimit(): int
    {
        return min(50, max(1, (int) ($this->operationalConfig()['idempotency_history_limit'] ?? 10)));
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

    private function requiresCondition(string $contentType): bool
    {
        return in_array($contentType, [
            DistributionItem::TYPE_MARKETPLACE_LISTING,
            DistributionItem::TYPE_CLASSIFIED_LISTING,
            DistributionItem::TYPE_PRODUCT_OFFER,
            DistributionItem::TYPE_VEHICLE_LISTING,
            DistributionItem::TYPE_HIRE_RENTAL_LISTING,
        ], true);
    }

    private function requiredState(DistributionItem $item, string $destination): array
    {
        $state = data_get($item->payload, "assisted_marketplaces.{$destination}");

        if (! is_array($state) || empty($state['package_hash'])) {
            throw new RuntimeException('Prepare the marketplace listing package first.');
        }

        return $state;
    }

    private function stateExpired(array $state): bool
    {
        if (empty($state['expires_at'])) {
            return false;
        }

        try {
            return Carbon::parse((string) $state['expires_at'])->isPast();
        } catch (Throwable) {
            return true;
        }
    }

    private function persistExpiredState(
        DistributionItem $item,
        string $destination,
        array $state
    ): array {
        if ((string) ($state['status'] ?? '') !== 'expired') {
            $state['status'] = 'expired';
            $state['expired_at'] = now()->toIso8601String();
            $this->persistState($item, $destination, $state);
        }

        return $state;
    }

    private function validatedExternalUrl(array $definition, string $externalUrl): string
    {
        $externalUrl = trim($externalUrl);

        if (! filter_var($externalUrl, FILTER_VALIDATE_URL)
            || strtolower((string) parse_url($externalUrl, PHP_URL_SCHEME)) !== 'https') {
            throw new InvalidArgumentException('The external marketplace listing URL must be a valid HTTPS URL.');
        }

        $host = strtolower((string) parse_url($externalUrl, PHP_URL_HOST));
        $allowedHosts = array_map(
            'strtolower',
            (array) ($definition['allowed_external_hosts'] ?? [])
        );

        if (! $this->hostAllowed($host, $allowedHosts)) {
            throw new InvalidArgumentException('The external URL does not belong to the selected marketplace.');
        }

        if ($this->normalisedPath($externalUrl)
            === $this->normalisedPath((string) $definition['official_posting_url'])) {
            throw new InvalidArgumentException('Provide the completed external listing URL, not the posting form URL.');
        }

        return $externalUrl;
    }

    private function normalisedPath(string $url): string
    {
        $path = '/' . ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        return rtrim($path, '/') ?: '/';
    }

    private function cachedOperation(
        DistributionItem $item,
        string $destination,
        string $operation,
        string $idempotencyKey,
        string $requestHash
    ): ?array {
        $stored = data_get(
            $item->payload,
            "assisted_marketplaces.{$destination}.operations.{$operation}.{$this->idempotencySlot($idempotencyKey)}"
        );

        if (! is_array($stored) || ($stored['status'] ?? null) !== 'succeeded') {
            return null;
        }

        if (! hash_equals((string) ($stored['idempotency_key'] ?? ''), $idempotencyKey)
            || ! hash_equals((string) ($stored['request_hash'] ?? ''), $requestHash)) {
            throw new RuntimeException('idempotency_key_conflict: the key was already used for different input.');
        }

        return (array) ($stored['result'] ?? []);
    }

    private function persistState(
        DistributionItem $item,
        string $destination,
        array $state
    ): void {
        $payload = (array) $item->payload;
        $operations = (array) data_get(
            $payload,
            "assisted_marketplaces.{$destination}.operations",
            []
        );
        $state['operations'] = $operations;
        data_set($payload, "assisted_marketplaces.{$destination}", $state);
        $item->update(['payload' => $payload]);
        $item->refresh();
    }

    private function persistOperation(
        DistributionItem $item,
        string $destination,
        string $operation,
        string $idempotencyKey,
        string $requestHash,
        array $result
    ): void {
        $payload = (array) $item->payload;
        $path = "assisted_marketplaces.{$destination}.operations.{$operation}";
        $operations = (array) data_get($payload, $path, []);
        $operations[$this->idempotencySlot($idempotencyKey)] = [
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'status' => 'succeeded',
            'result' => $result,
            'completed_at' => now()->toIso8601String(),
        ];
        $operations = array_slice(
            $operations,
            -$this->idempotencyHistoryLimit(),
            null,
            true
        );
        data_set($payload, $path, $operations);
        $item->update(['payload' => $payload]);
        $item->refresh();
    }

    private function idempotencySlot(string $idempotencyKey): string
    {
        return hash('sha256', $idempotencyKey);
    }

    private function assertPackageSize(array $package): void
    {
        $encoded = json_encode($package, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        if (strlen($encoded) > $this->maxPackageBytes()) {
            throw new InvalidArgumentException('The assisted marketplace package exceeds the configured size limit.');
        }
    }

    private function audit(
        User $user,
        string $destination,
        string $action,
        array $snapshot
    ): void {
        if (! Schema::hasTable('ext_social_media_distribution_audits')) {
            return;
        }

        DB::table('ext_social_media_distribution_audits')->insert([
            'user_id' => $user->getKey(),
            'social_media_platform_id' => null,
            'destination' => $destination,
            'action' => $action,
            'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }

    private function auditResult(array $result): array
    {
        unset($result['package'], $result['export_manifest']);

        return $result;
    }

    private function hostAllowed(string $host, array $allowedHosts): bool
    {
        foreach ($allowedHosts as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
                return true;
            }
        }

        return false;
    }

    private function requestHash(array $value): string
    {
        return hash('sha256', json_encode(
            $this->canonicalize($value),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
        ));
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
