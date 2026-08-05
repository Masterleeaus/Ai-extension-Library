<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class EbayListingIntegrationContractTest extends TestCase
{
    public function test_ebay_uses_official_sandbox_oauth_and_sell_scopes(): void
    {
        $config = file_get_contents(__DIR__ . '/../../config/social-media.php');
        $helper = file_get_contents(__DIR__ . '/../../System/Helpers/Ebay.php');
        $controller = file_get_contents(__DIR__ . '/../../System/Http/Controllers/Oauth/EbayController.php');

        $this->assertStringContainsString("'environment' => env('EBAY_ENVIRONMENT', 'sandbox')", $config);
        $this->assertStringContainsString('https://api.ebay.com/oauth/api_scope/sell.inventory', $config);
        $this->assertStringContainsString('https://api.ebay.com/oauth/api_scope/sell.account.readonly', $config);
        $this->assertStringContainsString('https://auth.sandbox.ebay.com/oauth2/authorize', $helper);
        $this->assertStringContainsString('/identity/v1/oauth2/token', $helper);
        $this->assertStringContainsString('hash_equals', $controller);
        $this->assertStringContainsString('Crypt::encryptString', $controller);
    }

    public function test_listing_flow_uses_inventory_offer_publish_and_withdraw_endpoints(): void
    {
        $helper = file_get_contents(__DIR__ . '/../../System/Helpers/Ebay.php');
        $service = file_get_contents(__DIR__ . '/../../System/Services/EbayListingService.php');

        $this->assertStringContainsString('/sell/inventory/v1/inventory_item/', $helper);
        $this->assertStringContainsString('/sell/inventory/v1/offer', $helper);
        $this->assertStringContainsString('/publish', $helper);
        $this->assertStringContainsString('/withdraw', $helper);
        $this->assertStringContainsString('createOrReplaceInventoryItem', $service);
        $this->assertStringContainsString('createOffer', $service);
        $this->assertStringContainsString('publishOffer', $service);
        $this->assertStringContainsString('withdrawOffer', $service);
        $this->assertStringContainsString('getOffer', $service);
    }

    public function test_listing_actions_are_tenant_scoped_approved_idempotent_and_audited(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/EbayListingService.php');
        $controller = file_get_contents(__DIR__ . '/../../System/Http/Controllers/EbayListingController.php');

        $this->assertStringContainsString('$item->user_id', $service);
        $this->assertStringContainsString('$account->user_id', $service);
        $this->assertStringContainsString("approval_status !== 'approved'", $service);
        $this->assertStringContainsString('idempotency_key', $service);
        $this->assertStringContainsString("DB::table('ext_social_media_distribution_audits')", $service);
        $this->assertStringContainsString('response()->json', $controller);
        $this->assertStringContainsString('buyerQuestionHandoff', $controller);
    }

    public function test_ebay_is_exposed_as_a_real_channel_only_after_adapter_registration(): void
    {
        $platform = file_get_contents(__DIR__ . '/../../System/Enums/PlatformEnum.php');
        $distribution = file_get_contents(__DIR__ . '/../../config/distribution.php');
        $provider = file_get_contents(__DIR__ . '/../../System/SocialMediaServiceProvider.php');

        $this->assertStringContainsString("case ebay = 'ebay'", $platform);
        $this->assertStringContainsString("'platform'          => 'ebay'", $distribution);
        $this->assertStringContainsString("'adapter_available' => true", $distribution);
        $this->assertStringContainsString('social-media.oauth.connect.ebay', $provider);
        $this->assertStringContainsString('ebay.publish', $provider);
        $this->assertStringContainsString('ebay.withdraw', $provider);
    }
}
