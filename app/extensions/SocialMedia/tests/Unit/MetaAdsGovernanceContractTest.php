<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MetaAdsGovernanceContractTest extends TestCase
{
    public function test_meta_ads_uses_existing_facebook_connection_with_minimum_ad_scopes(): void
    {
        $helper = file_get_contents(__DIR__ . '/../../System/Helpers/Facebook.php');
        $oauth = file_get_contents(__DIR__ . '/../../System/Http/Controllers/Oauth/FacebookController.php');

        $this->assertStringContainsString("'ads_read'", $oauth);
        $this->assertStringContainsString("'ads_management'", $oauth);
        $this->assertStringContainsString('marketing_access_token_encrypted', $oauth);
        $this->assertStringContainsString('Crypt::encryptString', $oauth);
        $this->assertStringContainsString('getAdAccounts', $helper);
        $this->assertStringContainsString('/campaigns', $helper);
        $this->assertStringContainsString('/adsets', $helper);
        $this->assertStringContainsString('/adcreatives', $helper);
        $this->assertStringContainsString('/ads', $helper);
        $this->assertStringContainsString('/insights', $helper);
        $this->assertStringContainsString('/previews', $helper);
    }

    public function test_provider_objects_are_created_paused_and_activation_is_separate(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/MetaAdsService.php');
        $config = file_get_contents(__DIR__ . '/../../config/distribution.php');

        $this->assertStringContainsString("'status' => 'PAUSED'", $service);
        $this->assertStringContainsString('syncPaused', $service);
        $this->assertStringContainsString('activate', $service);
        $this->assertStringContainsString('pause', $service);
        $this->assertStringContainsString('activation_confirmation', $service);
        $this->assertStringContainsString("'adapter_available' => true", $config);
        $this->assertStringContainsString("'platform'          => 'facebook'", $config);
        $this->assertStringContainsString("'approval_required' => true", $config);
    }

    public function test_budget_approval_is_immutable_fingerprinted_and_invalidated_by_edits(): void
    {
        $migration = file_get_contents(__DIR__ . '/../../database/migrations/2026_08_05_000002_create_ext_social_media_paid_media_tables.php');
        $service = file_get_contents(__DIR__ . '/../../System/Services/MetaAdsService.php');

        $this->assertStringContainsString("Schema::create('ext_social_media_paid_media_campaigns'", $migration);
        $this->assertStringContainsString("Schema::create('ext_social_media_paid_media_approvals'", $migration);
        $this->assertStringContainsString("Schema::create('ext_social_media_paid_media_receipts'", $migration);
        $this->assertStringContainsString('budget_minor', $migration);
        $this->assertStringContainsString('payload_fingerprint', $migration);
        $this->assertStringContainsString('approvalFingerprint', $service);
        $this->assertStringContainsString('hash_equals', $service);
        $this->assertStringContainsString('material_change_requires_new_approval', $service);
        $this->assertStringContainsString('decision', $service);
    }

    public function test_tenant_permissions_idempotency_and_spend_authority_are_fail_closed(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/MetaAdsService.php');
        $controller = file_get_contents(__DIR__ . '/../../System/Http/Controllers/MetaAdsController.php');

        $this->assertStringContainsString('$campaign->user_id', $service);
        $this->assertStringContainsString('$account->user_id', $service);
        $this->assertStringContainsString('Gate::allows', $service);
        $this->assertStringContainsString('paid_media.approve', $service);
        $this->assertStringContainsString('paid_media.activate', $service);
        $this->assertStringContainsString('idempotency_key', $service);
        $this->assertStringContainsString('Cache::lock', $controller);
        $this->assertStringContainsString('explicit_activation_required', $service);
        $this->assertStringNotContainsString('auto_activate', $service);
    }

    public function test_ai_recommendations_are_read_only_and_cannot_authorise_spend(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/MetaAdsService.php');
        $controller = file_get_contents(__DIR__ . '/../../System/Http/Controllers/MetaAdsController.php');

        $this->assertStringContainsString('recommendAudience', $service);
        $this->assertStringContainsString("'recommendation_only' => true", $service);
        $this->assertStringContainsString('approveBudget', $controller);
        $this->assertStringContainsString('activate', $controller);
        $this->assertStringContainsString('activation_confirmation', $controller);
    }
}
