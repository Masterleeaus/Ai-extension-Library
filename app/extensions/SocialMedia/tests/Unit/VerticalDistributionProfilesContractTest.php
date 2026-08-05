<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class VerticalDistributionProfilesContractTest extends TestCase
{
    public function test_catalogue_exposes_exactly_nine_canonical_vertical_families(): void
    {
        $catalogue = require __DIR__ . '/../../config/distribution.php';
        $verticals = (array) ($catalogue['verticals'] ?? []);

        $this->assertSame([
            'field-home-services',
            'accommodation',
            'real-estate',
            'salons-personal-care',
            'fitness-membership',
            'automotive-services',
            'ecommerce-retail',
            'hire-rental',
            'booking-capacity',
        ], array_keys($verticals));
        $this->assertCount(9, $verticals);
        $this->assertArrayNotHasKey('facilities-management', $verticals);
        $this->assertContains(
            'facilities-maintenance',
            (array) $verticals['field-home-services']['subtypes']
        );
    }

    public function test_catalogue_supports_vertical_specific_distribution_content_types(): void
    {
        $catalogue = require __DIR__ . '/../../config/distribution.php';
        $contentTypes = (array) ($catalogue['content_types'] ?? []);
        $model = file_get_contents(__DIR__ . '/../../System/Models/DistributionItem.php');

        foreach ([
            'room_stay_offer',
            'membership_offer',
            'class_session_offer',
            'booking_offer',
            'hire_rental_listing',
        ] as $contentType) {
            $this->assertContains($contentType, $contentTypes);
            $this->assertStringContainsString("'{$contentType}'", $model);
        }
    }

    public function test_every_vertical_declares_content_destinations_handoffs_and_guidance(): void
    {
        $catalogue = require __DIR__ . '/../../config/distribution.php';

        foreach ((array) $catalogue['verticals'] as $slug => $profile) {
            $this->assertNotEmpty($profile['label'] ?? null, $slug);
            $this->assertNotEmpty($profile['content_types'] ?? [], $slug);
            $this->assertNotEmpty($profile['destination_suitability'] ?? [], $slug);
            $this->assertNotEmpty($profile['required_fields'] ?? [], $slug);
            $this->assertNotEmpty($profile['media_guidance'] ?? [], $slug);
            $this->assertNotEmpty($profile['calls_to_action'] ?? [], $slug);
            $this->assertNotEmpty($profile['handoff_targets'] ?? [], $slug);
        }
    }

    public function test_generic_fallback_and_suitability_levels_are_explicit(): void
    {
        $catalogue = require __DIR__ . '/../../config/distribution.php';
        $generic = (array) ($catalogue['generic_profile'] ?? []);
        $service = file_get_contents(__DIR__ . '/../../System/Services/DistributionCapabilityService.php');

        $this->assertSame('generic-business', $generic['slug'] ?? null);
        $this->assertContains('social_post', (array) ($generic['content_types'] ?? []));

        foreach (['primary', 'supported', 'special-case', 'not-applicable'] as $level) {
            $this->assertStringContainsString($level, $service);
        }

        $this->assertStringContainsString('resolveVerticalProfile', $service);
        $this->assertStringContainsString('suitabilityFor', $service);
        $this->assertStringContainsString('forVerticalDestination', $service);
        $this->assertStringContainsString('vertical_context_resolver', $service);
        $this->assertStringContainsString('generic-business', $service);
    }

    public function test_vertical_profiles_preserve_provider_and_source_authority_boundaries(): void
    {
        $catalogue = require __DIR__ . '/../../config/distribution.php';
        $service = file_get_contents(__DIR__ . '/../../System/Services/DistributionCapabilityService.php');

        $this->assertSame(
            ['crm', 'workcore'],
            $catalogue['verticals']['field-home-services']['handoff_targets']['lead']
        );
        $this->assertSame(
            ['commerce', 'crm'],
            $catalogue['verticals']['ecommerce-retail']['handoff_targets']['lead']
        );
        $this->assertSame(
            ['hire', 'bookings', 'crm'],
            $catalogue['verticals']['hire-rental']['handoff_targets']['lead']
        );
        $this->assertStringContainsString('profile_version', $service);
        $this->assertStringContainsString('profile_provenance', $service);
        $this->assertStringContainsString('tenant_override', $service);
    }
}
