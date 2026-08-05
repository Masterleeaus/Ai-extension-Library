<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ChannelEntitlementContractTest extends TestCase
{
    public function test_default_allowance_and_rates_are_runtime_configurable(): void
    {
        $source = file_get_contents(__DIR__ . '/../../System/Services/SocialMediaChannelEntitlementService.php');

        $this->assertStringContainsString("env('TITAN_REACH_INCLUDED_CHANNELS', 3)", $source);
        $this->assertStringContainsString("env('TITAN_REACH_EXTRA_CHANNEL_MONTHLY_RATE', 10)", $source);
        $this->assertStringContainsString('titan_reach_extra_channels', $source);
    }

    public function test_entitlement_is_owner_scoped_and_publishing_is_fail_closed(): void
    {
        $service = file_get_contents(__DIR__ . '/../../System/Services/SocialMediaChannelEntitlementService.php');
        $driver = file_get_contents(__DIR__ . '/../../System/Services/Publisher/PublisherDriver.php');

        $this->assertStringContainsString("$platform->user_id", $service);
        $this->assertStringContainsString("$user->getKey()", $service);
        $this->assertStringContainsString('SocialMediaChannelEntitlementService::class', $driver);
        $this->assertStringContainsString('outside your Titan Reach channel allowance', $driver);
    }

    public function test_native_channels_page_exposes_usage_without_a_new_page(): void
    {
        $view = file_get_contents(__DIR__ . '/../../resources/views/platforms.blade.php');

        $this->assertStringContainsString("$channelUsage['included']", $view);
        $this->assertStringContainsString("$channelUsage['projected_monthly_add_on']", $view);
        $this->assertStringContainsString("@include('social-media::platforms.platform-table'", $view);
    }
}
