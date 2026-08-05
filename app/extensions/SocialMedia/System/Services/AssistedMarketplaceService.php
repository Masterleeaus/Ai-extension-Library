<?php

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class AssistedMarketplaceService
{
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
        $definition = $this->assertContext($user, $item, $destination, true);

        if ($cached = $this->cachedOperation($item, $destination, 'prepare', $idempotencyKey)) {
            return $cached;
        }

        $package = $this->validatedPackage($item, $definition, $input);
        $preparedAt = now();
        $expiresAt = $preparedAt->copy()->addDays(
            max(1, (int) config('social-media.assisted_marketplaces.default_expiry_days', 30))
        );
        $packageHash = hash('sha256', json_encode(
            $this->canonicalize($package),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
        ));
        $result = [
            'destination' => $destination,
            'destination_label' => (string) ($definition['label'] ?? $destination),
            'status' => 'ready_for_manual_post',
            'official_posting_url' => (string) $definition['official_posting_url'],
            'package_hash' => $packageHash,
            'prepared_at' => $preparedAt->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'package' => $package,
            'export_manifest' => $this->exportManifest($package, $definition),
            'manual_action_required' => true,
            'direct_publish_performed' => false,
        ];

        $this->persistState($item, $destination, $result);
        $this->persistOperation($item, $destination, 'prepare', $idempotencyKey, $result);
        $this->audit($user, $destination, 'assisted_package_prepared', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
            'result' => $result,
        ]);

        return $result;
    }

    public function openOfficialDestination(
        User $user,
        DistributionItem $item,
        string $destination,
        string $idempotencyKey
    ): array {
        $definition = $this->assertContext($user, $item, $destination, true);
        $state = $this->requiredState($item, $destination);

        if ($cached = $this->cachedOperation($item, $destination, 'open', $idempotencyKey)) {
            return $cached;
        }

        $result = [
            'destination' => $destination,
            'status' => 'opened_official_destination',
            'official_posting_url' => (string) $definition['official_posting_url'],
            'package_hash' => (string) ($state['package_hash'] ?? ''),
            'opened_at' => now()->toIso8601String(),
            'manual_action_required' => true,
            'direct_publish_performed' => false,
        ];

        $state = [...$state, ...$result];
        $this->persistState($item, $destination, $state);
        $this->persistOperation($item, $destination, 'open', $idempotencyKey, $result);
        $this->audit($user, $destination, 'assisted_destination_opened', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
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
        $definition = $this->assertContext($user, $item, $destination, true);
        $state = $this->requiredState($item, $destination);

        if ($cached = $this->cachedOperation($item, $destination, 'complete', $idempotencyKey)) {
            return $cached;
        }

        if (! $humanConfirmed) {
            throw new RuntimeException('Explicit human confirmation is required after posting on the marketplace.');
        }

        $externalUrl = trim($externalUrl);

        if (! filter_var($externalUrl, FILTER_VALIDATE_URL)
            || strtolower((string) parse_url($externalUrl, PHP_URL_SCHEME)) !== 'https') {
            throw new InvalidArgumentException('The external marketplace listing URL must be a valid HTTPS URL.');
        }

        $host = strtolower((string) parse_url($externalUrl, PHP_URL_HOST));
        $allowedHosts = array_map('strtolower', (array) ($definition['allowed_external_hosts'] ?? []));

        if (! $this->hostAllowed($host, $allowedHosts)) {
            throw new InvalidArgumentException('The external URL does not belong to the selected marketplace.');
        }

        $result = [
            'destination' => $destination,
            'status' => 'manually_published',
            'external_url' => $externalUrl,
            'external_listing_id' => $externalListingId,
            'package_hash' => (string) ($state['package_hash'] ?? ''),
            'human_confirmed' => true,
            'confirmed_by' => $user->getKey(),
            'confirmed_at' => now()->toIso8601String(),
            'manual_action_required' => false,
            'direct_publish_performed' => false,
        ];

        $state = [...$state, ...$result];
        $this->persistState($item, $destination, $state);
        $this->persistOperation($item, $destination, 'complete', $idempotencyKey, $result);
        $this->audit($user, $destination, 'assisted_listing_manually_confirmed', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
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
        $definition = $this->assertContext($user, $item, $destination, true);
        $state = $this->requiredState($item, $destination);

        if ($cached = $this->cachedOperation($item, $destination, 'renew', $idempotencyKey)) {
            return $cached;
        }

        if (! in_array((string) ($state['status'] ?? ''), [
            'manually_published',
            'renewal_ready_for_manual_post',
            'expired',
        ], true)) {
            throw new RuntimeException('The assisted listing must be manually published or expired before renewal preparation.');
        }

        $renewalCount = max(0, (int) ($state['renewal_count'] ?? 0)) + 1;
        $renewalDueAt = now()->addDays(
            max(1, (int) config('social-media.assisted_marketplaces.default_expiry_days', 30))
        );
        $result = [
            'destination' => $destination,
            'status' => 'renewal_ready_for_manual_post',
            'renewal_count' => $renewalCount,
            'renewal_prepared_at' => now()->toIso8601String(),
            'renewal_due_at' => $renewalDueAt->toIso8601String(),
            'official_posting_url' => (string) $definition['official_posting_url'],
            'package_hash' => (string) ($state['package_hash'] ?? ''),
            'package' => (array) ($state['package'] ?? []),
            'export_manifest' => (array) ($state['export_manifest'] ?? []),
            'manual_action_required' => true,
            'direct_publish_performed' => false,
        ];

        $state = [...$state, ...$result];
        $this->persistState($item, $destination, $state);
        $this->persistOperation($item, $destination, 'renew', $idempotencyKey, $result);
        $this->audit($user, $destination, 'assisted_renewal_prepared', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
            'result' => $result,
        ]);

        return $result;
    }

    public function enquiryHandoff(
        User $user,
        DistributionItem $item,
        string $destination,
        array $enquiry
    ): array {
        $this->assertContext($user, $item, $destination, false);
        $state = $this->requiredState($item, $destination);
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
        $this->assertContext($user, $item, $destination, false);

        return $this->requiredState($item, $destination);
    }

    private function assertContext(
        User $user,
        DistributionItem $item,
        string $destination,
        bool $requiresApproval
    ): array {
        if ((int) $item->user_id !== (int) $user->getKey()) {
            throw new RuntimeException('The assisted marketplace listing is outside the current tenant.');
        }

        $definition = $this->definition($destination);

        if (! in_array($item->content_type, (array) ($definition['content_types'] ?? []), true)) {
            throw new InvalidArgumentException('This distribution item cannot be prepared for the selected marketplace.');
        }

        $capability = $this->capabilities->forDestination($user, $destination, null);

        if (! $capability['available'] || $capability['mode'] !== DistributionItem::MODE_ASSISTED) {
            throw new RuntimeException('The assisted marketplace destination is unavailable.');
        }

        if ($requiresApproval && $item->approval_status !== 'approved') {
            throw new RuntimeException('The marketplace listing requires approval before assisted distribution.');
        }

        return $definition;
    }

    private function definition(string $destination): array
    {
        $definitions = (array) config('social-media.assisted_marketplaces.destinations', []);
        $definition = $definitions[$destination] ?? null;

        if (! is_array($definition) || ($definition['mode'] ?? null) !== DistributionItem::MODE_ASSISTED) {
            throw new InvalidArgumentException('Unsupported assisted marketplace destination.');
        }

        return $definition;
    }

    private function validatedPackage(
        DistributionItem $item,
        array $definition,
        array $input
    ): array {
        foreach ((array) ($definition['required_fields'] ?? []) as $field) {
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
        $maxImages = max(1, (int) config('social-media.assisted_marketplaces.max_images', 20));

        if ($imageUrls === [] || count($imageUrls) > $maxImages) {
            throw new InvalidArgumentException('Marketplace image count is invalid.');
        }

        foreach ($imageUrls as $imageUrl) {
            if (! filter_var($imageUrl, FILTER_VALIDATE_URL)
                || strtolower((string) parse_url($imageUrl, PHP_URL_SCHEME)) !== 'https') {
                throw new InvalidArgumentException('Every marketplace image URL must be a valid HTTPS URL.');
            }
        }

        $responseTemplates = array_values(array_filter(
            (array) data_get($input, 'response_templates', []),
            static fn ($template) => is_string($template) && trim($template) !== ''
        ));

        if ($responseTemplates === []) {
            $responseTemplates = [
                'Yes, this item is still available.',
                'Please confirm your preferred collection or delivery time.',
                'The price and condition are as shown in the listing.',
            ];
        }

        return [
            'content_type' => $item->content_type,
            'title' => trim((string) data_get($input, 'title')),
            'description' => trim((string) data_get($input, 'description')),
            'category' => trim((string) data_get($input, 'category')),
            'condition' => trim((string) data_get($input, 'condition')),
            'price_minor' => $priceMinor,
            'currency' => $currency,
            'location' => trim((string) data_get($input, 'location')),
            'image_urls' => $imageUrls,
            'image_overlays' => array_values((array) data_get($input, 'image_overlays', [])),
            'attributes' => (array) data_get($input, 'attributes', []),
            'delivery_details' => (array) data_get($input, 'delivery_details', []),
            'contact_preferences' => (array) data_get($input, 'contact_preferences', []),
            'response_templates' => $responseTemplates,
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
            ],
            'ordered_image_urls' => $package['image_urls'],
            'response_templates' => $package['response_templates'],
            'instructions' => [
                'Open the official marketplace posting page.',
                'Copy the prepared fields and upload the ordered images.',
                'Review the final listing on the marketplace before submitting it.',
                'Return to Titan Reach and confirm completion with the external listing URL.',
            ],
        ];
    }

    private function requiredState(DistributionItem $item, string $destination): array
    {
        $state = data_get($item->payload, "assisted_marketplaces.{$destination}");

        if (! is_array($state) || empty($state['package_hash'])) {
            throw new RuntimeException('Prepare the marketplace listing package first.');
        }

        return $state;
    }

    private function cachedOperation(
        DistributionItem $item,
        string $destination,
        string $operation,
        string $idempotencyKey
    ): ?array {
        $stored = data_get(
            $item->payload,
            "assisted_marketplaces.{$destination}.operations.{$operation}"
        );

        if (is_array($stored)
            && ($stored['status'] ?? null) === 'succeeded'
            && hash_equals((string) ($stored['idempotency_key'] ?? ''), $idempotencyKey)) {
            return (array) ($stored['result'] ?? []);
        }

        return null;
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
        array $result
    ): void {
        $payload = (array) $item->payload;
        data_set($payload, "assisted_marketplaces.{$destination}.operations.{$operation}", [
            'idempotency_key' => $idempotencyKey,
            'status' => 'succeeded',
            'result' => $result,
            'completed_at' => now()->toIso8601String(),
        ]);
        $item->update(['payload' => $payload]);
        $item->refresh();
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

    private function hostAllowed(string $host, array $allowedHosts): bool
    {
        foreach ($allowedHosts as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
                return true;
            }
        }

        return false;
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
