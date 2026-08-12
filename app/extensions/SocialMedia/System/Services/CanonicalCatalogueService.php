<?php

declare(strict_types=1);

namespace App\Extensions\SocialMedia\System\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class CanonicalCatalogueService
{
    private const AUDIT_DESTINATION = 'catalogue';

    private const HEALTH_STATUSES = ['healthy', 'degraded', 'unavailable', 'misconfigured'];

    public function __construct(
        private readonly CanonicalSourceRegistry $sources,
        private readonly DistributionCapabilityService $distributionCapabilities
    ) {}

    public function resolve(
        User $user,
        string $sourceKey,
        string $recordType,
        string $recordId,
        ?string $vertical = null,
        ?string $subtype = null
    ): array {
        $adapter = $this->sources->adapter($sourceKey);

        if (! $adapter) {
            return $this->rejection($user, 'catalogue_record_resolve_rejected', [
                'status' => 'unavailable',
                'reason' => 'source_adapter_unavailable',
                'source_system' => $sourceKey,
                'source_type' => $recordType,
                'source_id_hash' => hash('sha256', $recordId),
            ]);
        }

        try {
            if (! $adapter->available($user)) {
                return $this->rejection($user, 'catalogue_record_resolve_rejected', [
                    'status' => 'unavailable',
                    'reason' => 'source_adapter_unavailable',
                    'source_system' => $sourceKey,
                    'source_type' => $recordType,
                    'source_id_hash' => hash('sha256', $recordId),
                ]);
            }

            if (! $adapter->supports($recordType)) {
                return $this->rejection($user, 'catalogue_record_resolve_rejected', [
                    'status' => 'rejected',
                    'reason' => 'source_record_type_unsupported',
                    'source_system' => $sourceKey,
                    'source_type' => $recordType,
                    'source_id_hash' => hash('sha256', $recordId),
                ]);
            }

            $envelope = $adapter->fetch($user, $recordType, $recordId);
        } catch (Throwable $exception) {
            report($exception);

            return $this->rejection($user, 'catalogue_record_resolve_rejected', [
                'status' => 'degraded',
                'reason' => 'source_fetch_failed',
                'source_system' => $sourceKey,
                'source_type' => $recordType,
                'source_id_hash' => hash('sha256', $recordId),
            ]);
        }

        if (! is_array($envelope) || $envelope === []) {
            return $this->rejection($user, 'catalogue_record_resolve_rejected', [
                'status' => 'rejected',
                'reason' => 'source_record_not_found',
                'source_system' => $sourceKey,
                'source_type' => $recordType,
                'source_id_hash' => hash('sha256', $recordId),
            ]);
        }

        try {
            $normalized = $this->validateEnvelope($user, $sourceKey, $recordType, $envelope);
        } catch (RuntimeException|InvalidArgumentException $exception) {
            return $this->rejection($user, 'catalogue_record_resolve_rejected', [
                'status' => 'rejected',
                'reason' => $exception->getMessage(),
                'source_system' => $sourceKey,
                'source_type' => $recordType,
                'source_id_hash' => hash('sha256', $recordId),
            ]);
        }

        $profile = $this->distributionCapabilities->resolveVerticalProfile($vertical, $subtype, $user);
        $result = [
            'status' => 'ready',
            'source_system' => $sourceKey,
            'source_type' => $recordType,
            'source_id' => $normalized['source_id'],
            'vertical' => (string) ($profile['slug'] ?? 'generic-business'),
            'business_subtype' => $profile['resolved_subtype'] ?? null,
            'profile_version' => (string) ($profile['profile_version'] ?? 'unversioned'),
            'profile_provenance' => array_values((array) ($profile['profile_provenance'] ?? [])),
            'envelope' => $normalized,
        ];

        $this->audit($user, 'catalogue_record_resolved', [
            'status' => 'ready',
            'source_system' => $sourceKey,
            'source_type' => $recordType,
            'source_id_hash' => hash('sha256', $normalized['source_id']),
            'vertical' => $result['vertical'],
            'business_subtype' => $result['business_subtype'],
            'profile_version' => $result['profile_version'],
        ]);

        return $result;
    }

    public function search(
        User $user,
        string $sourceKey,
        string $recordType,
        array $filters = [],
        int $limit = 50
    ): array {
        $limit = max(1, min($limit, 100));
        $adapter = $this->sources->adapter($sourceKey);

        if (! $adapter) {
            return [
                'status' => 'unavailable',
                'reason' => 'source_adapter_unavailable',
                'source_system' => $sourceKey,
                'source_type' => $recordType,
                'records' => [],
                'rejections' => [],
            ];
        }

        try {
            if (! $adapter->available($user)) {
                return [
                    'status' => 'unavailable',
                    'reason' => 'source_adapter_unavailable',
                    'source_system' => $sourceKey,
                    'source_type' => $recordType,
                    'records' => [],
                    'rejections' => [],
                ];
            }

            if (! $adapter->supports($recordType)) {
                return [
                    'status' => 'rejected',
                    'reason' => 'source_record_type_unsupported',
                    'source_system' => $sourceKey,
                    'source_type' => $recordType,
                    'records' => [],
                    'rejections' => [],
                ];
            }

            $records = array_slice((array) $adapter->search($user, $recordType, $filters, $limit), 0, $limit);
        } catch (Throwable $exception) {
            report($exception);

            return [
                'status' => 'degraded',
                'reason' => 'source_search_failed',
                'source_system' => $sourceKey,
                'source_type' => $recordType,
                'records' => [],
                'rejections' => [],
            ];
        }

        $accepted = [];
        $rejections = [];

        foreach ($records as $record) {
            if (! is_array($record)) {
                $rejections[] = ['reason' => 'source_envelope_invalid'];
                continue;
            }

            try {
                $accepted[] = $this->validateEnvelope($user, $sourceKey, $recordType, $record);
            } catch (RuntimeException|InvalidArgumentException $exception) {
                $rejections[] = [
                    'reason' => $exception->getMessage(),
                    'source_id_hash' => isset($record['source_id'])
                        ? hash('sha256', (string) $record['source_id'])
                        : null,
                ];
            }
        }

        $this->audit($user, 'catalogue_search_completed', [
            'status' => $rejections === [] ? 'ready' : 'degraded',
            'source_system' => $sourceKey,
            'source_type' => $recordType,
            'accepted_count' => count($accepted),
            'rejected_count' => count($rejections),
        ]);

        return [
            'status' => $rejections === [] ? 'ready' : 'degraded',
            'reason' => $rejections === [] ? null : 'source_envelope_rejections',
            'source_system' => $sourceKey,
            'source_type' => $recordType,
            'records' => $accepted,
            'rejections' => $rejections,
        ];
    }

    public function transform(
        User $user,
        array $envelope,
        string $contentType,
        ?string $destination = null,
        ?string $vertical = null,
        ?string $subtype = null
    ): array {
        $sourceKey = trim((string) ($envelope['source_system'] ?? ''));
        $recordType = trim((string) ($envelope['source_type'] ?? ''));

        try {
            $normalized = $this->validateEnvelope($user, $sourceKey, $recordType, $envelope);
        } catch (RuntimeException|InvalidArgumentException $exception) {
            return $this->rejection($user, 'catalogue_transform_rejected', [
                'status' => 'rejected',
                'reason' => $exception->getMessage(),
                'source_system' => $sourceKey,
                'source_type' => $recordType,
            ]);
        }

        $profile = $this->distributionCapabilities->resolveVerticalProfile($vertical, $subtype, $user);
        $profileSlug = (string) ($profile['slug'] ?? 'generic-business');
        $resolvedSubtype = $profile['resolved_subtype'] ?? null;
        $mapping = $this->recordMapping($profileSlug, $sourceKey, $recordType);

        if ($mapping === []) {
            return $this->rejection($user, 'catalogue_transform_rejected', [
                'status' => 'rejected',
                'reason' => 'source_record_not_mapped_for_vertical',
                'source_system' => $sourceKey,
                'source_type' => $recordType,
                'source_id_hash' => hash('sha256', $normalized['source_id']),
                'vertical' => $profileSlug,
            ]);
        }

        if (! in_array($contentType, (array) ($mapping['content_types'] ?? []), true)) {
            return $this->rejection($user, 'catalogue_transform_rejected', [
                'status' => 'rejected',
                'reason' => 'source_record_content_type_unsupported',
                'source_system' => $sourceKey,
                'source_type' => $recordType,
                'source_id_hash' => hash('sha256', $normalized['source_id']),
                'vertical' => $profileSlug,
                'content_type' => $contentType,
            ]);
        }

        if ($destination !== null && $destination !== '') {
            $suitability = $this->distributionCapabilities->suitabilityFor(
                $profileSlug,
                $destination,
                $contentType,
                is_string($resolvedSubtype) ? $resolvedSubtype : null,
                $user
            );

            if (! (bool) ($suitability['available'] ?? false)) {
                return $this->rejection($user, 'catalogue_transform_rejected', [
                    'status' => 'rejected',
                    'reason' => 'destination_not_suitable',
                    'source_system' => $sourceKey,
                    'source_type' => $recordType,
                    'source_id_hash' => hash('sha256', $normalized['source_id']),
                    'vertical' => $profileSlug,
                    'content_type' => $contentType,
                    'destination' => $destination,
                    'suitability_reason' => $suitability['reason'] ?? null,
                ]);
            }
        }

        $missing = [];
        foreach ((array) ($mapping['required'] ?? []) as $requiredField) {
            $value = data_get($normalized['canonical_fields'], (string) $requiredField);
            if ($value === null || $value === '' || $value === []) {
                $missing[] = (string) $requiredField;
            }
        }

        if ($missing !== []) {
            return $this->rejection($user, 'catalogue_transform_rejected', [
                'status' => 'rejected',
                'reason' => 'missing_canonical_fields',
                'source_system' => $sourceKey,
                'source_type' => $recordType,
                'source_id_hash' => hash('sha256', $normalized['source_id']),
                'vertical' => $profileSlug,
                'content_type' => $contentType,
                'destination' => $destination,
                'missing_fields' => array_values(array_unique($missing)),
            ]);
        }

        $fields = [];
        foreach ((array) ($mapping['fields'] ?? []) as $targetField => $sourcePath) {
            $value = data_get($normalized['canonical_fields'], (string) $sourcePath);
            if ($value !== null && $value !== '' && $value !== []) {
                $fields[(string) $targetField] = $value;
            }
        }

        $confidence = isset($normalized['attribution_confidence']) && is_numeric($normalized['attribution_confidence'])
            ? max(0.0, min(1.0, (float) $normalized['attribution_confidence']))
            : 1.0;
        $destinationFields = $this->destinationFields($profileSlug, $resolvedSubtype, $destination);

        $result = [
            'status' => 'ready',
            'content_type' => $contentType,
            'destination' => $destination,
            'fields' => $fields,
            'destination_fields' => $destinationFields,
            'canonical_source' => [
                'source_system' => $normalized['source_system'],
                'source_type' => $normalized['source_type'],
                'source_id' => $normalized['source_id'],
                'source_version' => $normalized['source_version'],
                'source_updated_at' => $normalized['source_updated_at'],
                'company_id' => $normalized['company_id'],
                'provenance' => $normalized['provenance'],
                'authority' => $normalized['authority'],
            ],
            'vertical' => $profileSlug,
            'business_subtype' => $resolvedSubtype,
            'profile_version' => (string) ($profile['profile_version'] ?? 'unversioned'),
            'profile_provenance' => array_values((array) ($profile['profile_provenance'] ?? [])),
            'attribution_confidence' => $confidence,
        ];

        $this->audit($user, 'catalogue_record_transformed', [
            'status' => 'ready',
            'source_system' => $sourceKey,
            'source_type' => $recordType,
            'source_id_hash' => hash('sha256', $normalized['source_id']),
            'source_version' => $normalized['source_version'],
            'vertical' => $profileSlug,
            'business_subtype' => $resolvedSubtype,
            'profile_version' => $result['profile_version'],
            'content_type' => $contentType,
            'destination' => $destination,
            'attribution_confidence' => $confidence,
        ]);

        return $result;
    }

    public function health(User $user): array
    {
        $health = $this->sources->health($user);
        $bounded = [];

        foreach ($health as $sourceKey => $snapshot) {
            $status = (string) ($snapshot['status'] ?? 'degraded');
            if (! in_array($status, self::HEALTH_STATUSES, true)) {
                $status = 'degraded';
            }

            $bounded[$sourceKey] = [
                'status' => $status,
                'reason' => isset($snapshot['reason']) ? (string) $snapshot['reason'] : null,
                'checked_at' => $snapshot['checked_at'] ?? now()->toIso8601String(),
            ];
        }

        $this->audit($user, 'catalogue_feed_health', [
            'sources' => array_map(
                static fn (array $snapshot): array => [
                    'status' => $snapshot['status'],
                    'reason' => $snapshot['reason'],
                ],
                $bounded
            ),
        ]);

        return $bounded;
    }

    public function handoff(User $user, string $target, string $handoffType, array $context): array
    {
        $profile = $this->distributionCapabilities->resolveVerticalProfile(
            isset($context['vertical']) ? (string) $context['vertical'] : null,
            isset($context['subtype']) ? (string) $context['subtype'] : null,
            $user
        );
        $allowedTargets = $this->flattenHandoffTargets((array) ($profile['handoff_targets'] ?? []));

        if (! in_array($target, $allowedTargets, true)) {
            return $this->rejection($user, 'catalogue_handoff_rejected', [
                'status' => 'rejected',
                'reason' => 'handoff_target_not_allowed',
                'target' => $target,
                'handoff_type' => $handoffType,
                'vertical' => (string) ($profile['slug'] ?? 'generic-business'),
            ]);
        }

        $definition = $this->sources->definition($target);
        $adapter = $this->sources->adapter($target);

        if ($definition === [] || ! $adapter) {
            return $this->rejection($user, 'catalogue_handoff_rejected', [
                'status' => 'unavailable',
                'reason' => 'handoff_unavailable',
                'target' => $target,
                'handoff_type' => $handoffType,
                'vertical' => (string) ($profile['slug'] ?? 'generic-business'),
            ]);
        }

        if (! in_array($handoffType, (array) ($definition['handoff_types'] ?? []), true)) {
            return $this->rejection($user, 'catalogue_handoff_rejected', [
                'status' => 'rejected',
                'reason' => 'handoff_type_unsupported',
                'target' => $target,
                'handoff_type' => $handoffType,
                'vertical' => (string) ($profile['slug'] ?? 'generic-business'),
            ]);
        }

        try {
            if (! $adapter->available($user)) {
                throw new RuntimeException('handoff_unavailable');
            }

            $response = (array) $adapter->handoff($user, $handoffType, $this->boundedHandoffContext($context));
        } catch (Throwable $exception) {
            report($exception);

            return $this->rejection($user, 'catalogue_handoff_rejected', [
                'status' => 'unavailable',
                'reason' => 'handoff_unavailable',
                'target' => $target,
                'handoff_type' => $handoffType,
                'vertical' => (string) ($profile['slug'] ?? 'generic-business'),
            ]);
        }

        $status = (string) ($response['status'] ?? 'accepted');
        if (! in_array($status, ['accepted', 'queued', 'completed', 'rejected'], true)) {
            $status = 'accepted';
        }

        $result = [
            'status' => $status,
            'target' => $target,
            'handoff_type' => $handoffType,
            'reference' => isset($response['reference']) ? (string) $response['reference'] : null,
            'reason' => isset($response['reason']) ? (string) $response['reason'] : null,
            'vertical' => (string) ($profile['slug'] ?? 'generic-business'),
            'profile_version' => (string) ($profile['profile_version'] ?? 'unversioned'),
        ];

        $this->audit($user, 'catalogue_handoff_completed', $result);

        return $result;
    }

    private function validateEnvelope(User $user, string $sourceKey, string $recordType, array $envelope): array
    {
        foreach (['source_system', 'source_type', 'source_id', 'tenant_id', 'canonical_fields', 'provenance', 'authority'] as $required) {
            if (! array_key_exists($required, $envelope)) {
                throw new InvalidArgumentException('source_envelope_invalid');
            }
        }

        $envelopeSource = trim((string) $envelope['source_system']);
        $envelopeType = trim((string) $envelope['source_type']);
        $sourceId = trim((string) $envelope['source_id']);

        if ($envelopeSource === '' || $sourceId === '' || $envelopeType === '') {
            throw new InvalidArgumentException('source_envelope_invalid');
        }

        if ($sourceKey !== '' && $envelopeSource !== $sourceKey) {
            throw new RuntimeException('source_system_mismatch');
        }

        if ($recordType !== '' && $envelopeType !== $recordType) {
            throw new RuntimeException('source_type_mismatch');
        }

        if ((string) $envelope['tenant_id'] !== (string) $user->getKey()) {
            throw new RuntimeException('tenant_mismatch');
        }

        if (! is_array($envelope['canonical_fields']) || ! is_array($envelope['provenance'])) {
            throw new InvalidArgumentException('source_envelope_invalid');
        }

        return [
            'source_system' => $envelopeSource,
            'source_type' => $envelopeType,
            'source_id' => $sourceId,
            'tenant_id' => (string) $envelope['tenant_id'],
            'company_id' => $envelope['company_id'] ?? null,
            'source_version' => isset($envelope['source_version']) ? (string) $envelope['source_version'] : null,
            'source_updated_at' => $envelope['source_updated_at'] ?? null,
            'canonical_fields' => $envelope['canonical_fields'],
            'provenance' => array_values(array_filter(array_map('strval', $envelope['provenance']))),
            'authority' => is_array($envelope['authority'])
                ? array_values(array_filter(array_map('strval', $envelope['authority'])))
                : (string) $envelope['authority'],
            'attribution_confidence' => $envelope['attribution_confidence'] ?? null,
        ];
    }

    private function recordMapping(string $vertical, string $sourceKey, string $recordType): array
    {
        $catalogue = $this->catalogue();
        $key = $sourceKey . ':' . $recordType;
        $verticalMapping = (array) data_get($catalogue, "verticals.{$vertical}.records.{$key}", []);

        if ($verticalMapping !== []) {
            return $verticalMapping;
        }

        return (array) data_get($catalogue, "verticals.generic-business.records.{$key}", []);
    }

    private function destinationFields(string $vertical, mixed $subtype, ?string $destination): array
    {
        if ($destination === null || $destination === '') {
            return [];
        }

        $catalogue = $this->catalogue();
        $subtype = is_string($subtype) ? $subtype : null;

        if ($subtype) {
            $override = (array) data_get(
                $catalogue,
                "verticals.{$vertical}.subtype_overrides.{$subtype}.destination_fields.{$destination}",
                []
            );

            if ($override !== []) {
                return array_values(array_filter(array_map('strval', $override)));
            }
        }

        return array_values(array_filter(array_map(
            'strval',
            (array) data_get($catalogue, "destination_fields.{$destination}", [])
        )));
    }

    private function catalogue(): array
    {
        $base = require dirname(__DIR__, 2) . '/config/catalogues.php';
        $configured = (array) config('social-media.catalogues', []);

        return array_replace_recursive($base, $configured);
    }

    private function flattenHandoffTargets(array $targets): array
    {
        $flat = [];
        foreach ($targets as $value) {
            foreach ((array) $value as $target) {
                if (is_string($target) && $target !== '') {
                    $flat[] = $target;
                }
            }
        }

        return array_values(array_unique($flat));
    }

    private function boundedHandoffContext(array $context): array
    {
        return array_intersect_key($context, array_flip([
            'vertical',
            'subtype',
            'source_system',
            'source_type',
            'source_id',
            'engagement_id',
            'distribution_item_id',
            'destination',
            'reason',
            'attribution_confidence',
        ]));
    }

    private function rejection(User $user, string $action, array $snapshot): array
    {
        $this->audit($user, $action, $snapshot);

        return $snapshot;
    }

    private function audit(User $user, string $action, array $snapshot): void
    {
        if (! Schema::hasTable('ext_social_media_distribution_audits')) {
            return;
        }

        DB::table('ext_social_media_distribution_audits')->insert([
            'user_id' => $user->getKey(),
            'social_media_platform_id' => null,
            'destination' => self::AUDIT_DESTINATION,
            'action' => $action,
            'snapshot' => json_encode($this->safeSnapshot($snapshot), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }

    private function safeSnapshot(array $snapshot): array
    {
        foreach ($snapshot as $key => $value) {
            if (preg_match('/token|secret|password|authorization|credential/i', (string) $key)) {
                unset($snapshot[$key]);
                continue;
            }

            if (is_array($value)) {
                $snapshot[$key] = $this->safeSnapshot($value);
            }
        }

        return $snapshot;
    }
}
