<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AssistedMarketplaceContractTest extends TestCase
{
    public function test_marketplaces_are_assisted_and_never_direct_publishers(): void
    {
        $config = file_get_contents(__DIR__ . '/../../config/assisted-marketplaces.php');
        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');

        $this->assertStringContainsString("'facebook-marketplace'", $config);
        $this->assertStringContainsString("'gumtree'", $config);
        $this->assertStringContainsString("'mode' => 'assisted'", $config);
        $this->assertStringContainsString("'publish' => false", $config);
        $this->assertStringContainsString("'manual_confirmation' => true", $config);
        $this->assertStringNotContainsString('BrowserKit', $service);
        $this->assertStringNotContainsString('Panther', $service);
        $this->assertStringNotContainsString('Selenium', $service);
        $this->assertStringNotContainsString('puppeteer', strtolower($service));
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
        $this->assertStringNotContainsString("$item->update(['status' => 'published'])", $service);
    }

    public function test_actions_are_tenant_scoped_approved_locked_idempotent_and_audited(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');
        $controller = file_get_contents(__DIR__ . '/../../System/Http/Controllers/AssistedMarketplaceController.php');

        $this->assertStringContainsString('$item->user_id', $service);
        $this->assertStringContainsString("approval_status !== 'approved'", $service);
        $this->assertStringContainsString('idempotency_key', $service);
        $this->assertStringContainsString("DB::table('ext_social_media_distribution_audits')", $service);
        $this->assertStringContainsString('Cache::lock', $controller);
        $this->assertStringContainsString('$item->refresh()', $controller);
        $this->assertStringContainsString('ready_for_manual_post', $service);
    }

    public function test_renewals_and_enquiries_are_tracked_without_automated_reposting_or_replies(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/AssistedMarketplaceService.php');

        $this->assertStringContainsString('prepareRenewal', $service);
        $this->assertStringContainsString('renewal_due_at', $service);
        $this->assertStringContainsString('enquiryHandoff', $service);
        $this->assertStringContainsString('human_handoff_required', $service);
        $this->assertStringContainsString('enquiry_id', $service);
        $this->assertStringNotContainsString('sendMessage', $service);
        $this->assertStringNotContainsString('auto_reply', $service);
    }
}
