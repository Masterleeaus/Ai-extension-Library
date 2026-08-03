<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanShell;

use InvalidArgumentException;

/**
 * Canonical registry for the five customer-facing Titan platform applications.
 *
 * Vertical templates, WorkCore modules and internal engines are deliberately
 * excluded. Legacy shell slugs are resolved through an explicit migration map.
 */
final class PlatformApplicationRegistry
{
    public const VERSION = '2.0.0';

    private const APPLICATIONS = [
        'titan-zero' => [
            'name' => 'Titan Zero',
            'audience' => ['owner', 'manager', 'administrator'],
            'purpose' => 'Business administration, governance, approvals and cross-platform management.',
            'schema' => 'titan-zero',
            'owns_global_settings' => true,
            'internal_engines' => [],
            'legacy_slugs' => ['titan-money', 'titan-teams', 'titan-analytics', 'titan-locker', 'titan-office', 'titan-quality'],
        ],
        'titan-go' => [
            'name' => 'Titan Go',
            'audience' => ['dispatcher', 'supervisor', 'field-worker', 'cleaner'],
            'purpose' => 'Dispatch, mobile field execution, evidence, attendance and offline job completion.',
            'schema' => 'titan-go',
            'owns_global_settings' => false,
            'internal_engines' => [],
            'legacy_slugs' => ['titan-dispatch'],
        ],
        'titan-launch' => [
            'name' => 'Titan Launch',
            'audience' => ['owner', 'manager', 'sales', 'marketing'],
            'purpose' => 'Business creation, vertical generation, launch planning and growth workflows.',
            'schema' => 'titan-launch',
            'owns_global_settings' => false,
            'internal_engines' => ['titan-sprout'],
            'legacy_slugs' => ['titan-sprout', 'titan-marketing', 'titan-social'],
        ],
        'titan-desk' => [
            'name' => 'Titan Desk',
            'audience' => ['reception', 'dispatcher', 'sales', 'manager'],
            'purpose' => 'Business communications, intake, qualification, booking requests and quote requests.',
            'schema' => 'titan-desk',
            'owns_global_settings' => false,
            'internal_engines' => [],
            'legacy_slugs' => ['titan-front-desk'],
        ],
        'titan-hub' => [
            'name' => 'Titan Hub',
            'audience' => ['customer'],
            'purpose' => 'Customer self-service for bookings, quotes, invoices, payments, documents and service history.',
            'schema' => 'titan-hub',
            'owns_global_settings' => false,
            'internal_engines' => [],
            'legacy_slugs' => [],
        ],
    ];

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return self::APPLICATIONS;
    }

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::APPLICATIONS);
    }

    public static function has(string $slug): bool
    {
        return isset(self::APPLICATIONS[$slug]);
    }

    /** @return array<string, mixed> */
    public static function get(string $slug): array
    {
        $canonical = self::canonicalSlug($slug);

        if ($canonical === null) {
            throw new InvalidArgumentException("Unknown Titan platform application: {$slug}");
        }

        return ['slug' => $canonical] + self::APPLICATIONS[$canonical];
    }

    public static function canonicalSlug(?string $slug): ?string
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return 'titan-zero';
        }

        if (self::has($slug)) {
            return $slug;
        }

        foreach (self::APPLICATIONS as $canonical => $application) {
            if (in_array($slug, $application['legacy_slugs'], true)) {
                return $canonical;
            }
        }

        return null;
    }

    /** @return array<string, string> */
    public static function legacyMap(): array
    {
        $map = [];

        foreach (self::APPLICATIONS as $canonical => $application) {
            foreach ($application['legacy_slugs'] as $legacy) {
                $map[$legacy] = $canonical;
            }
        }

        return $map;
    }

    public static function isLegacySlug(string $slug): bool
    {
        return isset(self::legacyMap()[$slug]);
    }
}
