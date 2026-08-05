<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AssistedMarketplaceContractTest extends TestCase
{
    public function test_marketplaces_are_assisted_and_never_direct_publishers(): void
    {
        $capabilities = file_get_contents(__DIR__ . '/../../config/distribution.php');
        $operations = file_get_contents(__DIR__ . '/../../config/assisted-marketplaces.php');
        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');

        $this->assertStringContainsString("'facebook-marketplace'", $capabilities);
        $this->assertStringContainsString("'gumtree'", $capabilities);
        $this->assertStringContainsString("'mode'              => 'assisted'", $capabilities);
        $this->assertStringContainsString("'publish' => false", $capabilities);
        $this->assertStringContainsString("'manual_confirmation' => true", $capabilities);
        $this->assertStringContainsString('official_posting_url', $operations);
        $this->assertStringContainsString('allowed_external_hosts', $operations);
        $this->assertStringNotContainsString('BrowserKit', $service);
        $this->assertStringNotContainsString('Panther', $service);
        $this->assertStringNotContainsString('Selenium', $service);
        $this->assertStringNotContainsString('puppeteer', strtolower($service));
    }

    public function test_listing_packages_consume_the_canonical_nine_vertical_profiles(): void
    {
        $verticals = require __DIR__ . '/../../config/vertical-distribution.php';
        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');

        $this->assertCount(9, (array) ($verticals['verticals'] ?? []));
        $this->assertStringContainsString('forVerticalDestination', $service);
        $this->assertStringContainsString('content_type', $service);
        $this->assertStringContainsString('vertical', $service);
        $this->assertStringContainsString('business_subtype', $service);
        $this->assertStringContainsString('profile_version', $service);
        $this->assertStringContainsString('profile_provenance', $service);
        $this->assertStringContainsString('media_guidance', $service);
        $this->assertStringContainsString('calls_to_action', $service);
        $this->assertStringContainsString('handoff_targets', $service);
        $this->assertStringContainsString('compliance_warnings', $service);
        $this->assertStringContainsString('destination_not_applicable_to_vertical', $service);
    }

    public function test_listing_package_contains_copy_media_price_location_and_official_destination(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');
        $config = file_get_contents(__DIR__ . '/../../config/assisted-marketplaces.php');

        $this->assertStringContainsString('preparePackage', $service);
        $this->assertStringContainsString('title', $service);
        $this->assertStringContainsString('description', $service);
        $this->assertStringContainsString('price_minor', $service);
        $this->assertStringContainsString('currency', $service);
        $this->assertStringContainsString('location', $service);
        $this->assertStringContainsString('image_urls', $service);
        $this->assertStringContainsString('response_templates', $service);
        $this->assertStringContainsString('official_posting_url', $service);
        $this->assertStringContainsString('facebook.com/marketplace/create', $config);
        $this->assertStringContainsString('gumtree.com.au', $config);
    }

    public function test_manual_completion_requires_human_confirmation_and_valid_external_url(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');
        $controller = file_get_contents(__DIR__ . '/../../System/Http/Controllers/AssistedMarketplaceController.php');

        $this->assertStringContainsString('markCompleted', $service);
        $this->assertStringContainsString('human_confirmed', $controller);
        $this->assertStringContainsString('external_url', $controller);
        $this->assertStringContainsString('FILTER_VALIDATE_URL', $service);
        $this->assertStringContainsString('allowed_external_hosts', $service);
        $this->assertStringContainsString('manually_published', $service);
        $this->assertStringNotContainsString("\$item->update(['status' => 'published'])", $service);
        $this->assertStringContainsString('direct_publish_performed', $service);
    }

    public function test_actions_are_tenant_scoped_approved_locked_idempotent_and_audited(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');
        $controller = file_get_contents(__DIR__ . '/../../System/Http/Controllers/AssistedMarketplaceController.php');

        $this->assertStringContainsString('$item->user_id', $service);
        $this->assertStringContainsString("approval_status !== 'approved'", $service);
        $this->assertStringContainsString('abort(404)', $controller);
        $this->assertStringContainsString('idempotency_key', $service);
        $this->assertStringContainsString('request_hash', $service);
        $this->assertStringContainsString('idempotency_key_conflict', $service);
        $this->assertStringContainsString('idempotencySlot', $service);
        $this->assertStringContainsString('array_slice($operations, -50', $service);
        $this->assertStringContainsString("DB::table('ext_social_media_distribution_audits')", $service);
        $this->assertStringContainsString('Cache::lock', $controller);
        $this->assertStringContainsString('$item->refresh()', $controller);
        $this->assertStringContainsString('ready_for_manual_post', $service);
        $this->assertStringContainsString('package_hash', $service);
    }

    public function test_renewals_and_enquiries_are_tracked_without_automated_reposting_or_replies(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');

        $this->assertStringContainsString('prepareRenewal', $service);
        $this->assertStringContainsString("'operation' => 'renew'", $service);
        $this->assertStringContainsString('renewal_due_at', $service);
        $this->assertStringContainsString('enquiryHandoff', $service);
        $this->assertStringContainsString('human_handoff_required', $service);
        $this->assertStringContainsString('enquiry_id', $service);
        $this->assertStringNotContainsString('sendMessage', $service);
        $this->assertStringNotContainsString('auto_reply', $service);
        $this->assertStringContainsString('automated_reply_sent', $service);
    }

    public function test_routes_are_state_changing_posts_and_no_new_blade_page_is_added(): void
    {
        $provider = file_get_contents(__DIR__ . '/../../System/SocialMediaServiceProvider.php');
        $docs = file_get_contents(__DIR__ . '/../../docs/TITAN-REACH-ASSISTED-MARKETPLACE-FILE-MAP.md');

        $this->assertStringContainsString("assisted/{destination}/prepare", $provider);
        $this->assertStringContainsString("assisted/{destination}/open", $provider);
        $this->assertStringContainsString("assisted/{destination}/complete", $provider);
        $this->assertStringContainsString("assisted/{destination}/renew", $provider);
        $this->assertStringContainsString("assisted/{destination}/enquiry-handoff", $provider);
        $this->assertStringContainsString('No new Blade page', $docs);
        $this->assertStringContainsString('issue #274', strtolower($docs));
    }
}
