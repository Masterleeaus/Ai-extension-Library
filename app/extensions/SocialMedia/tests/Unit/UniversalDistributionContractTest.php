<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UniversalDistributionContractTest extends TestCase
{
    public function test_distribution_item_is_additive_and_preserves_social_post_authority(): void
    {
        $model = file_get_contents(__DIR__ . '/../../System/Models/DistributionItem.php');
        $post = file_get_contents(__DIR__ . '/../../System/Models/SocialMediaPost.php');
        $migration = file_get_contents(__DIR__ . '/../../database/migrations/2026_08_05_000001_create_ext_social_media_distribution_items_table.php');

        $this->assertStringContainsString("protected \$table = 'ext_social_media_distribution_items'", $model);
        $this->assertStringContainsString("'social_post'", $model);
        $this->assertStringContainsString("'marketplace_listing'", $model);
        $this->assertStringContainsString("'property_listing'", $model);
        $this->assertStringContainsString("'vehicle_listing'", $model);
        $this->assertStringContainsString("'job_listing'", $model);
        $this->assertStringContainsString('socialMediaPost(): BelongsTo', $model);
        $this->assertStringContainsString('distributionItem(): HasOne', $post);
        $this->assertStringContainsString('Use fromSocialMediaPost() for canonical social post mappings.', $model);
        $this->assertStringContainsString("unset(\$attributes['social_media_post_id'])", $model);
        $this->assertStringContainsString("Schema::create('ext_social_media_distribution_items'", $migration);
        $this->assertStringNotContainsString("Schema::dropIfExists('ext_social_media_posts')", $migration);
    }

    public function test_destination_modes_and_unsupported_capabilities_are_explicit(): void
    {
        $model = file_get_contents(__DIR__ . '/../../System/Models/DistributionItem.php');
        $config = file_get_contents(__DIR__ . '/../../config/distribution.php');
        $service = file_get_contents(__DIR__ . '/../../System/Services/DistributionCapabilityService.php');

        $this->assertStringContainsString("MODE_DIRECT = 'direct'", $model);
        $this->assertStringContainsString("MODE_PARTNER = 'partner'", $model);
        $this->assertStringContainsString("MODE_ASSISTED = 'assisted'", $model);
        $this->assertStringContainsString("MODE_EXPORT_ONLY = 'export_only'", $model);
        $this->assertStringContainsString("'facebook-marketplace'", $config);
        $this->assertStringContainsString("'gumtree'", $config);
        $this->assertStringContainsString("'ebay'", $config);
        $this->assertStringContainsString("'adapter_available' => false", $config);
        $this->assertStringContainsString("'effective_capabilities'", $service);
        $this->assertStringContainsString('account_destination_mismatch', $service);
    }

    public function test_capability_snapshots_are_tenant_scoped_and_auditable(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/DistributionCapabilityService.php');
        $migration = file_get_contents(__DIR__ . '/../../database/migrations/2026_08_05_000001_create_ext_social_media_distribution_items_table.php');

        $this->assertStringContainsString('$account->user_id', $service);
        $this->assertStringContainsString('$user->getKey()', $service);
        $this->assertStringContainsString('SocialMediaChannelEntitlementService', $service);
        $this->assertStringContainsString("DB::table('ext_social_media_distribution_audits')", $service);
        $this->assertStringContainsString("Schema::create('ext_social_media_distribution_audits'", $migration);
    }
}
