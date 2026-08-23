<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\WorkCore\Channels;

use App\Domains\WorkCore\System\Modules\Channels\Models\Channel;
use App\Domains\WorkCore\System\Modules\Channels\Services\{
    ChannelService,
    InventorySyncService,
    OrderSyncService,
    PricingSyncService,
};
use Tests\TestCase;

class ChannelSyncOperationsTest extends TestCase
{
    private ChannelService $channelService;
    private InventorySyncService $inventoryService;
    private OrderSyncService $orderService;
    private PricingSyncService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->channelService = app(ChannelService::class);
        $this->inventoryService = app(InventorySyncService::class);
        $this->orderService = app(OrderSyncService::class);
        $this->pricingService = app(PricingSyncService::class);
    }

    public function test_register_channel_with_airbnb(): void
    {
        $data = [
            'name' => 'My Airbnb',
            'type' => 'airbnb',
            'api_token' => 'test_token_123',
            'api_secret' => 'test_secret_456',
            'enabled' => true,
        ];

        $channel = $this->channelService->registerChannel($data, 1, 1);

        $this->assertNotNull($channel->id);
        $this->assertEquals('airbnb', $channel->type);
        $this->assertEquals('My Airbnb', $channel->name);
        $this->assertTrue($channel->enabled);
    }

    public function test_channel_authentication_flow(): void
    {
        $channel = Channel::factory()->create([
            'type' => 'airbnb',
            'api_token' => 'test_token',
        ]);

        $this->assertFalse($channel->isAuthenticated());

        $channel->markAuthenticated();

        $this->assertTrue($channel->isAuthenticated());
        $this->assertNotNull($channel->authenticated_at);
    }

    public function test_list_channels_with_filters(): void
    {
        Channel::factory()->count(3)->create(['tenant_id' => 1, 'type' => 'airbnb']);
        Channel::factory()->count(2)->create(['tenant_id' => 1, 'type' => 'booking']);
        Channel::factory()->count(1)->create(['tenant_id' => 2, 'type' => 'airbnb']);

        $result = $this->channelService->listChannels(1, ['type' => 'airbnb']);

        $this->assertGreaterThanOrEqual(3, $result->total());
    }

    public function test_update_channel_settings(): void
    {
        $channel = Channel::factory()->create(['type' => 'amazon']);
        $newSettings = ['markup_percentage' => 15, 'auto_sync' => true];

        $updated = $this->channelService->updateChannel(
            $channel,
            ['settings' => $newSettings],
            $channel->tenant_id,
            1
        );

        $this->assertEquals($newSettings, $updated->settings);
    }

    public function test_disconnect_channel(): void
    {
        $channel = Channel::factory()->create();

        $success = $this->channelService->disconnectChannel($channel, $channel->tenant_id, 1);

        $this->assertTrue($success);
        $this->assertSoftDeleted($channel);
    }

    public function test_get_sync_status(): void
    {
        $channel = Channel::factory()->create([
            'auth_status' => 'authenticated',
            'last_sync_at' => now(),
        ]);

        $status = $this->channelService->getSyncStatus($channel);

        $this->assertEquals($channel->id, $status['channel_id']);
        $this->assertEquals('authenticated', $status['auth_status']);
        $this->assertTrue($status['is_authenticated']);
    }

    public function test_channel_has_inventory_tracking(): void
    {
        $channel = Channel::factory()->create();

        $this->inventoryService->pushInventoryUpdate($channel, 'PROD-001', 50);
        $this->inventoryService->pushInventoryUpdate($channel, 'PROD-002', 30);

        $state = $this->inventoryService->pullInventoryState($channel);

        $this->assertEquals(2, $state['inventory_count']);
    }

    public function test_channel_has_order_tracking(): void
    {
        $channel = Channel::factory()->create();

        // Orders would be synced here
        $result = $this->orderService->getPendingOrders($channel->id);

        $this->assertIsArray($result);
        $this->assertEquals($channel->id, $result['channel_id']);
    }

    public function test_channel_has_pricing_tracking(): void
    {
        $channel = Channel::factory()->create();

        $this->pricingService->pushPriceUpdates($channel, 'PROD-001', 99.99, 'USD');
        $this->pricingService->pushPriceUpdates($channel, 'PROD-002', 149.99, 'USD');

        $history = $this->pricingService->getPriceHistory($channel->id);

        $this->assertGreaterThanOrEqual(2, $history['items_count']);
    }
}
