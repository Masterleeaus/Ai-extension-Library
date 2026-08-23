<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class VerticalDistributionProfilesContractTest extends TestCase
{
    private function catalogue(): array
    {
        return require __DIR__ . '/../../config/vertical-distribution.php';
    }

    public function test_catalogue_exposes_exactly_nine_canonical_vertical_families(): void
    {
        $verticals = (array) ($this->catalogue()['verticals'] ?? []);

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

    public function test_catalogue_supports_and_assigns_every_distribution_content_type(): void
    {
        $catalogue = $this->catalogue();
        $contentTypes = (array) ($catalogue['content_types'] ?? []);
        $model = file_get_contents(__DIR__ . '/../../System/Models/DistributionItem.php');
        $assigned = [];

        foreach ((array) $catalogue['verticals'] as $profile) {
            $assigned = [...$assigned, ...(array) ($profile['content_types'] ?? [])];
        }

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

        foreach ($contentTypes as $contentType) {
            $this->assertContains($contentType, $assigned, $contentType);
        }
    }

    public function test_every_vertical_declares_content_destinations_handoffs_and_guidance(): void
    {
        $allowedSuitability = ['primary', 'supported', 'special-case', 'not-applicable'];
        $destinationCatalogue = require __DIR__ . '/../../config/distribution.php';
        $destinations = array_keys((array) ($destinationCatalogue['destinations'] ?? []));

        foreach ((array) $this->catalogue()['verticals'] as $slug => $profile) {
            $this->assertNotEmpty($profile['label'] ?? null, $slug);
            $this->assertNotEmpty($profile['content_types'] ?? [], $slug);
            $this->assertSame(
                $destinations,
                array_keys((array) ($profile['destination_suitability'] ?? [])),
                $slug
            );
            $this->assertNotEmpty($profile['required_fields'] ?? [], $slug);
            $this->assertNotEmpty($profile['media_guidance'] ?? [], $slug);
            $this->assertNotEmpty($profile['calls_to_action'] ?? [], $slug);
            $this->assertNotEmpty($profile['handoff_targets'] ?? [], $slug);

            foreach ((array) $profile['destination_suitability'] as $suitability) {
                $this->assertContains($suitability, $allowedSuitability, $slug);
            }
        }
    }

    public function test_generic_fallback_and_suitability_levels_are_explicit(): void
    {
        $generic = (array) ($this->catalogue()['generic_profile'] ?? []);
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
        $this->assertStringContainsString("\$profile['content_types'] = DistributionItem::contentTypes()", $service);
        $this->assertStringContainsString('normaliseSuitability', $service);
    }

    public function test_aliases_context_snapshots_and_tenant_overrides_fail_closed(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/DistributionCapabilityService.php');

        $this->assertStringContainsString("'facilities-management' => 'field-home-services'", $service);
        $this->assertStringContainsString("'facilities-management' => 'facilities-maintenance'", $service);
        $this->assertStringContainsString('inferredSubtype', $service);
        $this->assertStringContainsString('applyTenantOverride', $service);
        $this->assertStringContainsString('array_intersect', $service);
        $this->assertStringContainsString('SUITABILITY_NOT_APPLICABLE', $service);
        $this->assertStringContainsString("data_get(\$resolved, 'resolved.capabilities.vertical_family')", $service);
        $this->assertStringContainsString("data_get(\$resolved, 'resolved.capabilities.subtype')", $service);
        $this->assertStringContainsString("'context_id' => \$resolved['context_id'] ?? null", $service);
        $this->assertStringContainsString("'context_hash' => \$resolved['context_hash'] ?? null", $service);
        $this->assertStringContainsString('vertical_context_id', $service);
        $this->assertStringContainsString('vertical_context_hash', $service);
    }

    public function test_catalogues_are_cached_and_one_destination_definition_is_reused(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/DistributionCapabilityService.php');

        $this->assertStringContainsString('destinationCatalogueCache', $service);
        $this->assertStringContainsString('verticalCatalogueCache', $service);
        $this->assertStringContainsString('verticalProfilesCache', $service);
        $this->assertStringContainsString('capabilityForDefinition', $service);
        $this->assertStringContainsString('suitabilityForDefinition', $service);
    }

    public function test_vertical_profiles_preserve_provider_and_source_authority_boundaries(): void
    {
        $catalogue = $this->catalogue();
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
        $this->assertStringContainsString('allowedHandoffTargets', $service);
    }
}
