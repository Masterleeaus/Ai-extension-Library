<?php

declare(strict_types=1);

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Contracts\CanonicalSourceAdapterContract;
use App\Models\User;
use Throwable;

class CanonicalSourceRegistry
{
    private const HEALTH_STATUSES = ['healthy', 'degraded', 'unavailable', 'misconfigured'];

    private ?array $definitionsCache = null;

    public function adapter(string $sourceKey): ?CanonicalSourceAdapterContract
    {
        $definition = $this->definition($sourceKey);
        $resolver = $definition['resolver'] ?? null;

        if (! is_string($resolver) || trim($resolver) === '') {
            return null;
        }

        try {
            if (! app()->bound($resolver) && ! class_exists($resolver)) {
                return null;
            }

            $adapter = app($resolver);

            if (! $adapter instanceof CanonicalSourceAdapterContract) {
                return null;
            }

            if ($adapter->sourceKey() !== $sourceKey) {
                return null;
            }

            return $adapter;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function capabilities(User $user): array
    {
        $result = [];

        foreach ($this->definitions() as $sourceKey => $definition) {
            $resolver = $definition['resolver'] ?? null;
            $adapter = $this->adapter($sourceKey);

            if (! is_string($resolver) || trim($resolver) === '') {
                $result[$sourceKey] = [
                    'status' => 'unavailable',
                    'available' => false,
                    'reason' => 'source_adapter_not_configured',
                    'record_types' => array_values((array) ($definition['record_types'] ?? [])),
                ];

                continue;
            }

            if (! $adapter) {
                $result[$sourceKey] = [
                    'status' => 'misconfigured',
                    'available' => false,
                    'reason' => 'source_adapter_invalid',
                    'record_types' => array_values((array) ($definition['record_types'] ?? [])),
                ];

                continue;
            }

            try {
                $available = $adapter->available($user);
            } catch (Throwable $exception) {
                report($exception);
                $available = false;
            }

            $result[$sourceKey] = [
                'status' => $available ? 'healthy' : 'unavailable',
                'available' => $available,
                'reason' => $available ? null : 'source_adapter_unavailable',
                'record_types' => array_values(array_filter(
                    (array) ($definition['record_types'] ?? []),
                    static fn (mixed $recordType): bool => is_string($recordType) && $recordType !== ''
                )),
            ];
        }

        return $result;
    }

    public function health(User $user): array
    {
        $capabilities = $this->capabilities($user);
        $result = [];

        foreach ($capabilities as $sourceKey => $capability) {
            if (! $capability['available']) {
                $result[$sourceKey] = [
                    'status' => $capability['status'],
                    'reason' => $capability['reason'],
                ];

                continue;
            }

            $adapter = $this->adapter($sourceKey);

            if (! $adapter) {
                $result[$sourceKey] = [
                    'status' => 'misconfigured',
                    'reason' => 'source_adapter_invalid',
                ];

                continue;
            }

            try {
                $health = (array) $adapter->health($user);
                $status = (string) ($health['status'] ?? 'degraded');

                if (! in_array($status, self::HEALTH_STATUSES, true)) {
                    $status = 'degraded';
                }

                $result[$sourceKey] = [
                    'status' => $status,
                    'reason' => isset($health['reason']) ? (string) $health['reason'] : null,
                    'checked_at' => $health['checked_at'] ?? now()->toIso8601String(),
                ];
            } catch (Throwable $exception) {
                report($exception);
                $result[$sourceKey] = [
                    'status' => 'degraded',
                    'reason' => 'source_health_check_failed',
                    'checked_at' => now()->toIso8601String(),
                ];
            }
        }

        return $result;
    }

    public function definition(string $sourceKey): array
    {
        return (array) ($this->definitions()[$sourceKey] ?? []);
    }

    public function definitions(): array
    {
        if ($this->definitionsCache !== null) {
            return $this->definitionsCache;
        }

        $catalogue = require dirname(__DIR__, 2) . '/config/catalogues.php';
        $base = (array) ($catalogue['sources'] ?? []);
        $configured = (array) config('social-media.catalogues.sources', []);
        $overrides = array_intersect_key($configured, $base);

        return $this->definitionsCache = array_replace_recursive($base, $overrides);
    }
}
