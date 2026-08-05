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

        if ($this->stateExpired($state)) {
            throw new RuntimeException('The prepared marketplace package has expired and must be reviewed again.');
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

        if (! in_array((string) ($state['status'] ?? ''), [
            'manually_published',
            'expired',
        ], true)) {
            throw new RuntimeException('The assisted listing must be manually published or expired before renewal preparation.');
        }

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
            $state['status'] = 'expired';
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

        return [
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

        if (rtrim($externalUrl, '/') === rtrim((string) $definition['official_posting_url'], '/')) {
            throw new InvalidArgumentException('Provide the completed external listing URL, not the posting form URL.');
        }

        return $externalUrl;
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
        $operations = array_slice($operations, -50, null, true);
        data_set($payload, $path, $operations);
        $item->update(['payload' => $payload]);
        $item->refresh();
    }

    private function idempotencySlot(string $idempotencyKey): string
    {
        return hash('sha256', $idempotencyKey);
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
+}
diff --git a/app/extensions/SocialMedia/System/SocialMediaServiceProvider.php b/app/extensions/SocialMedia/System/SocialMediaServiceProvider.php
index fd4a85230..9ecddef4f 100644
--- a/app/extensions/SocialMedia/System/SocialMediaServiceProvider.php
+++ b/app/extensions/SocialMedia/System/SocialMediaServiceProvider.php
@@ -5,6 +5,7 @@
 namespace App\Extensions\SocialMedia\System;
 
 use App\Domains\Marketplace\Contracts\UninstallExtensionServiceProviderInterface;
+use App\Extensions\SocialMedia\System\Http\Controllers\AssistedMarketplaceController;
 use App\Extensions\SocialMedia\System\Http\Controllers\Common\DemoDataController;
 use App\Extensions\SocialMedia\System\Http\Controllers\Common\SocialMediaCampaignCommonController;
 use App\Extensions\SocialMedia\System\Http\Controllers\Common\SocialMediaCompanyCommonController;
@@ -94,6 +95,10 @@ public function registerConfig(): static
     {
         $this->mergeConfigFrom(__DIR__ . '/../config/social-media.php', 'social-media');
         $this->mergeConfigFrom(__DIR__ . '/../config/ebay.php', 'social-media.ebay');
+        $this->mergeConfigFrom(
+            __DIR__ . '/../config/assisted-marketplaces.php',
+            'social-media.assisted_marketplaces'
+        );
         config()->set('social-media.distribution.destinations.ebay', config('social-media.ebay.destination'));
 
         return $this;
@@ -195,6 +200,13 @@ private function registerRoutes(): static
                         $router->post('distribution/{item}/ebay/withdraw', [EbayListingController::class, 'withdraw'])->name('ebay.withdraw');
                         $router->post('distribution/{item}/ebay/reconcile', [EbayListingController::class, 'reconcile'])->name('ebay.reconcile');
                         $router->post('distribution/{item}/ebay/buyer-question-handoff', [EbayListingController::class, 'buyerQuestionHandoff'])->name('ebay.buyer-question-handoff');
+
+                        $router->post('distribution/{item}/assisted/{destination}/prepare', [AssistedMarketplaceController::class, 'prepare'])->name('assisted.prepare');
+                        $router->post('distribution/{item}/assisted/{destination}/open', [AssistedMarketplaceController::class, 'open'])->name('assisted.open');
+                        $router->post('distribution/{item}/assisted/{destination}/complete', [AssistedMarketplaceController::class, 'complete'])->name('assisted.complete');
+                        $router->post('distribution/{item}/assisted/{destination}/renew', [AssistedMarketplaceController::class, 'renew'])->name('assisted.renew');
+                        $router->post('distribution/{item}/assisted/{destination}/enquiry-handoff', [AssistedMarketplaceController::class, 'enquiryHandoff'])->name('assisted.enquiry-handoff');
+                        $router->get('distribution/{item}/assisted/{destination}/status', [AssistedMarketplaceController::class, 'status'])->name('assisted.status');
                     });
 
                 $router
diff --git a/app/extensions/SocialMedia/config/assisted-marketplaces.php b/app/extensions/SocialMedia/config/assisted-marketplaces.php
new file mode 100644
index 000000000..229932f70
--- /dev/null
+++ b/app/extensions/SocialMedia/config/assisted-marketplaces.php
@@ -0,0 +1,43 @@
+<?php
+
+return [
+    'default_expiry_days' => 30,
+    'max_images' => 20,
+    'destinations' => [
+        'facebook-marketplace' => [
+            'label' => 'Facebook Marketplace',
+            'official_posting_url' => 'https://www.facebook.com/marketplace/create/item',
+            'allowed_external_hosts' => [
+                'facebook.com',
+                'www.facebook.com',
+                'm.facebook.com',
+            ],
+            'completion_states' => [
+                'ready_for_manual_post',
+                'opened_official_destination',
+                'manually_published',
+                'renewal_ready_for_manual_post',
+            ],
+        ],
+        'gumtree' => [
+            'label' => 'Gumtree Australia',
+            'official_posting_url' => 'https://www.gumtree.com.au/p-post-ad.html',
+            'allowed_external_hosts' => [
+                'gumtree.com.au',
+                'www.gumtree.com.au',
+            ],
+            'completion_states' => [
+                'ready_for_manual_post',
+                'opened_official_destination',
+                'manually_published',
+                'renewal_ready_for_manual_post',
+            ],
+            'posting_guidance' => [
+                'Use the correct category and physical location for the product or service.',
+                'Prepare a unique advertisement rather than reposting duplicate copy.',
+                'Business service advertisements may require the Services for Hire category.',
+                'Keep buyer conversations and payment activity within Gumtree where available.',
+            ],
+        ],
+    ],
+];
diff --git a/app/extensions/SocialMedia/docs/TITAN-REACH-ASSISTED-MARKETPLACE-FILE-MAP.md b/app/extensions/SocialMedia/docs/TITAN-REACH-ASSISTED-MARKETPLACE-FILE-MAP.md
new file mode 100644
index 000000000..81f842fac
--- /dev/null
+++ b/app/extensions/SocialMedia/docs/TITAN-REACH-ASSISTED-MARKETPLACE-FILE-MAP.md
@@ -0,0 +1,79 @@
+# Titan Reach assisted marketplace file map
+
+Issue: #271
+
+Depends on: #337
+
+## Destinations
+
+- Facebook Marketplace
+- Gumtree Australia
+
+Both destinations are implemented as `assisted` workflows. Titan Reach prepares and tracks the package; the authenticated business user submits the listing on the marketplace.
+
+## Existing-file-first implementation
+
+| New file | Existing source/template | Why it is necessary |
+|---|---|---|
+| `config/assisted-marketplaces.php` | `config/distribution.php` | Holds operational metadata not represented by provider capabilities: official handoff URLs, allowed completion hosts, expiry/image limits and destination-specific manual posting guidance. It does not duplicate publishing modes or provider capabilities. |
+| `System/Services/AssistedMarketplaceService.php` | `System/Services/EbayListingService.php` and `System/Services/DistributionCapabilityService.php` | A provider-neutral application service is required for package preparation, manual handoff, completion confirmation, renewal and enquiry handoff. |
+| `System/Http/Controllers/AssistedMarketplaceController.php` | `System/Http/Controllers/EbayListingController.php` | Governed HTTP actions are required for the future native Listings UI and other approved clients. |
+| `tests/Unit/AssistedMarketplaceContractTest.php` | `tests/Unit/VerticalDistributionProfilesContractTest.php` and `tests/Unit/EbayListingIntegrationContractTest.php` | Prevents regression into simulated publishing, browser automation, non-vertical packages, unsafe URL completion or ungoverned actions. |
+
+## Existing file edited
+
+`System/SocialMediaServiceProvider.php` is edited in place to:
+
+- merge the assisted operational config;
+- register the existing controller with the current authenticated SocialMedia route group;
+- expose prepare, open, complete, renew, enquiry-handoff and status actions.
+
+No new route provider is introduced.
+
+## Nine-vertical behaviour
+
+Every prepared package calls `DistributionCapabilityService::forVerticalDestination()` and receives the canonical profile from #337 or the generic-business fallback.
+
+The package records:
+
+- canonical vertical and label;
+- business subtype and whether it is recognised;
+- destination suitability;
+- vertical/provider required fields;
+- media guidance;
+- calls to action;
+- authoritative handoff targets;
+- compliance warnings;
+- profile version and provenance;
+- shared vertical context ID and hash where available.
+
+Facilities maintenance resolves to the `field-home-services` family and `facilities-maintenance` subtype.
+
+## Manual-only safety boundary
+
+- No API publication is claimed.
+- No browser automation, scraping, form simulation or credential collection is used.
+- `open` returns the official destination URL; it does not operate the marketplace account.
+- Completion requires explicit human confirmation and a valid HTTPS listing URL on an allowed marketplace host.
+- The posting form URL itself is not accepted as proof of completion.
+- Titan Reach records `manually_published`; it does not change the canonical `DistributionItem` status to provider-published.
+- Renewals create a reviewed manual package and never repost automatically.
+- Enquiries are deduplicated and handed to a human/authoritative workflow; no automated reply is sent.
+
+## Governance and data authority
+
+- Tenant ownership and `DistributionItem` approval are required before preparation or external handoff.
+- Destination suitability and provider capability fail closed.
+- A per-tenant/item/destination lock serialises mutations.
+- Every idempotent action stores a request hash; reuse of a key with different input fails with `idempotency_key_conflict`.
+- Distribution audits record package, completion, renewal and enquiry events.
+- Titan Commerce, WorkCore, Bookings, Property, Automotive, Hire and CRM remain authoritative for source records.
+- Titan Reach stores the approved listing snapshot and external URL only.
+
+## UI boundary
+
+**No new Blade page** is introduced by #271. The native customer-facing Listings and assisted-posting interface remains issue #274 and must be duplicated from the closest existing SocialMedia/MagicAI Blade page.
+
+## Live validation boundary
+
+The service is source-contract ready. Final browser validation requires authenticated marketplace accounts and current marketplace posting forms. Titan Reach must continue to rely on human submission unless an approved official provider or partner API is later obtained.
diff --git a/app/extensions/SocialMedia/tests/Unit/AssistedMarketplaceContractTest.php b/app/extensions/SocialMedia/tests/Unit/AssistedMarketplaceContractTest.php
new file mode 100644
index 000000000..0bf75924b
--- /dev/null
+++ b/app/extensions/SocialMedia/tests/Unit/AssistedMarketplaceContractTest.php
@@ -0,0 +1,128 @@
+<?php
+
+namespace Tests\Unit;
+
+use PHPUnit\Framework\TestCase;
+
+class AssistedMarketplaceContractTest extends TestCase
+{
+    public function test_marketplaces_are_assisted_and_never_direct_publishers(): void
+    {
+        $capabilities = file_get_contents(__DIR__ . '/../../config/distribution.php');
+        $operations = file_get_contents(__DIR__ . '/../../config/assisted-marketplaces.php');
+        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');
+
+        $this->assertStringContainsString("'facebook-marketplace'", $capabilities);
+        $this->assertStringContainsString("'gumtree'", $capabilities);
+        $this->assertStringContainsString("'mode'              => 'assisted'", $capabilities);
+        $this->assertStringContainsString("'publish' => false", $capabilities);
+        $this->assertStringContainsString("'manual_confirmation' => true", $capabilities);
+        $this->assertStringContainsString('official_posting_url', $operations);
+        $this->assertStringContainsString('allowed_external_hosts', $operations);
+        $this->assertStringNotContainsString('BrowserKit', $service);
+        $this->assertStringNotContainsString('Panther', $service);
+        $this->assertStringNotContainsString('Selenium', $service);
+        $this->assertStringNotContainsString('puppeteer', strtolower($service));
+    }
+
+    public function test_listing_packages_consume_the_canonical_nine_vertical_profiles(): void
+    {
+        $verticals = require __DIR__ . '/../../config/vertical-distribution.php';
+        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');
+
+        $this->assertCount(9, (array) ($verticals['verticals'] ?? []));
+        $this->assertStringContainsString('forVerticalDestination', $service);
+        $this->assertStringContainsString('content_type', $service);
+        $this->assertStringContainsString('vertical', $service);
+        $this->assertStringContainsString('business_subtype', $service);
+        $this->assertStringContainsString('profile_version', $service);
+        $this->assertStringContainsString('profile_provenance', $service);
+        $this->assertStringContainsString('media_guidance', $service);
+        $this->assertStringContainsString('calls_to_action', $service);
+        $this->assertStringContainsString('handoff_targets', $service);
+        $this->assertStringContainsString('compliance_warnings', $service);
+        $this->assertStringContainsString('destination_not_applicable_to_vertical', $service);
+    }
+
+    public function test_listing_package_contains_copy_media_price_location_and_official_destination(): void
+    {
+        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');
+        $config = file_get_contents(__DIR__ . '/../../config/assisted-marketplaces.php');
+
+        $this->assertStringContainsString('preparePackage', $service);
+        $this->assertStringContainsString('title', $service);
+        $this->assertStringContainsString('description', $service);
+        $this->assertStringContainsString('price_minor', $service);
+        $this->assertStringContainsString('currency', $service);
+        $this->assertStringContainsString('location', $service);
+        $this->assertStringContainsString('image_urls', $service);
+        $this->assertStringContainsString('response_templates', $service);
+        $this->assertStringContainsString('official_posting_url', $service);
+        $this->assertStringContainsString('facebook.com/marketplace/create', $config);
+        $this->assertStringContainsString('gumtree.com.au', $config);
+    }
+
+    public function test_manual_completion_requires_human_confirmation_and_valid_external_url(): void
+    {
+        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');
+        $controller = file_get_contents(__DIR__ . '/../../System/Http/Controllers/AssistedMarketplaceController.php');
+
+        $this->assertStringContainsString('markCompleted', $service);
+        $this->assertStringContainsString('human_confirmed', $controller);
+        $this->assertStringContainsString('external_url', $controller);
+        $this->assertStringContainsString('FILTER_VALIDATE_URL', $service);
+        $this->assertStringContainsString('allowed_external_hosts', $service);
+        $this->assertStringContainsString('manually_published', $service);
+        $this->assertStringNotContainsString("\$item->update(['status' => 'published'])", $service);
+        $this->assertStringContainsString('direct_publish_performed', $service);
+    }
+
+    public function test_actions_are_tenant_scoped_approved_locked_idempotent_and_audited(): void
+    {
+        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');
+        $controller = file_get_contents(__DIR__ . '/../../System/Http/Controllers/AssistedMarketplaceController.php');
+
+        $this->assertStringContainsString('$item->user_id', $service);
+        $this->assertStringContainsString("approval_status !== 'approved'", $service);
+        $this->assertStringContainsString('abort(404)', $controller);
+        $this->assertStringContainsString('idempotency_key', $service);
+        $this->assertStringContainsString('request_hash', $service);
+        $this->assertStringContainsString('idempotency_key_conflict', $service);
+        $this->assertStringContainsString('idempotencySlot', $service);
+        $this->assertStringContainsString('array_slice($operations, -50', $service);
+        $this->assertStringContainsString("DB::table('ext_social_media_distribution_audits')", $service);
+        $this->assertStringContainsString('Cache::lock', $controller);
+        $this->assertStringContainsString('$item->refresh()', $controller);
+        $this->assertStringContainsString('ready_for_manual_post', $service);
+        $this->assertStringContainsString('package_hash', $service);
+    }
+
+    public function test_renewals_and_enquiries_are_tracked_without_automated_reposting_or_replies(): void
+    {
+        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');
+
+        $this->assertStringContainsString('prepareRenewal', $service);
+        $this->assertStringContainsString("'operation' => 'renew'", $service);
+        $this->assertStringContainsString('renewal_due_at', $service);
+        $this->assertStringContainsString('enquiryHandoff', $service);
+        $this->assertStringContainsString('human_handoff_required', $service);
+        $this->assertStringContainsString('enquiry_id', $service);
+        $this->assertStringNotContainsString('sendMessage', $service);
+        $this->assertStringNotContainsString('auto_reply', $service);
+        $this->assertStringContainsString('automated_reply_sent', $service);
+    }
+
+    public function test_routes_are_state_changing_posts_and_no_new_blade_page_is_added(): void
+    {
+        $provider = file_get_contents(__DIR__ . '/../../System/SocialMediaServiceProvider.php');
+        $docs = file_get_contents(__DIR__ . '/../../docs/TITAN-REACH-ASSISTED-MARKETPLACE-FILE-MAP.md');
+
+        $this->assertStringContainsString("assisted/{destination}/prepare", $provider);
+        $this->assertStringContainsString("assisted/{destination}/open", $provider);
+        $this->assertStringContainsString("assisted/{destination}/complete", $provider);
+        $this->assertStringContainsString("assisted/{destination}/renew", $provider);
+        $this->assertStringContainsString("assisted/{destination}/enquiry-handoff", $provider);
+        $this->assertStringContainsString('No new Blade page', $docs);
+        $this->assertStringContainsString('issue #274', strtolower($docs));
+    }
+}
