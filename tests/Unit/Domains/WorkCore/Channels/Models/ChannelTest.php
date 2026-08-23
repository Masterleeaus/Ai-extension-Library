<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\WorkCore\Channels\Models;

use App\Domains\WorkCore\System\Modules\Channels\Models\{
    Channel,
    ChannelInventory,
    ChannelMapping,
    ChannelOrder,
    ChannelPricing,
};
use Tests\TestCase;

class ChannelTest extends TestCase
{
    private Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->channel = Channel::factory()->create([
            'type' => 'airbnb',
            'api_token' => 'test_token',
            'api_secret' => 'test_secret',
            'enabled' => true,
        ]);
    }

    public function test_channel_has_relationships(): void
    {
        ChannelMapping::factory()->create(['channel_id' => $this->channel->id]);
        ChannelInventory::factory()->create(['channel_id' => $this->channel->id]);
        ChannelOrder::factory()->create(['channel_id' => $this->channel->id]);
        ChannelPricing::factory()->create(['channel_id' => $this->channel->id]);

        $this->assertNotEmpty($this->channel->mappings);
        $this->assertNotEmpty($this->channel->inventory);
        $this->assertNotEmpty($this->channel->orders);
        $this->assertNotEmpty($this->channel->pricings);
    }

    public function test_channel_can_be_enabled_disabled(): void
    {
        $this->assertTrue($this->channel->enabled);

        $this->channel->update(['enabled' => false]);
        $this->assertFalse($this->channel->enabled);
    }

    public function test_channel_scope_enabled(): void
    {
        Channel::factory()->create(['tenant_id' => $this->channel->tenant_id, 'enabled' => false]);

        $enabledChannels = Channel::byTenant($this->channel->tenant_id)->enabled()->get();

        $this->assertTrue($enabledChannels->every(fn($ch) => $ch->enabled));
    }

    public function test_channel_scope_by_tenant(): void
    {
        $channels = Channel::byTenant($this->channel->tenant_id)->get();

        $this->assertTrue($channels->every(fn($ch) => $ch->tenant_id === $this->channel->tenant_id));
    }

    public function test_channel_scope_by_type(): void
    {
        Channel::factory()->create(['tenant_id' => $this->channel->tenant_id, 'type' => 'booking']);

        $airbnbChannels = Channel::byTenant($this->channel->tenant_id)->byType('airbnb')->get();

        $this->assertTrue($airbnbChannels->every(fn($ch) => $ch->type === 'airbnb'));
    }

    public function test_channel_is_authenticated(): void
    {
        $this->assertFalse($this->channel->isAuthenticated());

        $this->channel->markAuthenticated();

        $this->assertTrue($this->channel->isAuthenticated());
    }

    public function test_channel_mark_authentication_failed(): void
    {
        $this->channel->markAuthenticationFailed();

        $this->assertEquals('failed', $this->channel->auth_status);
    }

    public function test_channel_credentials_cast(): void
    {
        $credentials = ['client_id' => '123', 'client_secret' => 'secret'];
        $this->channel->update(['credentials' => $credentials]);

        $this->assertIsArray($this->channel->credentials);
        $this->assertEquals($credentials, $this->channel->credentials);
    }
}
