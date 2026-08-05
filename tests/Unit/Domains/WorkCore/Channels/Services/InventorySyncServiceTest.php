<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\WorkCore\Channels\Services;

use App\Domains\WorkCore\System\Modules\Channels\Models\{Channel, ChannelInventory};
use App\Domains\WorkCore\System\Modules\Channels\Services\InventorySyncService;
use Tests\TestCase;

class InventorySyncServiceTest extends TestCase
{
    private InventorySyncService $inventoryService;
    private Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = app(InventorySyncService::class);
        $this->channel = Channel::factory()->create(['type' => 'airbnb']);
    }

    public function test_prevent_overselling_with_insufficient_quantity(): void
    {
        $inventory = ChannelInventory::factory()->create([
            'channel_id' => $this->channel->id,
            'product_id' => 'PROD-001',
            'available_qty' => 5,
        ]);

        $this->assertFalse($this->inventoryService->preventOverselling('PROD-001', 10, $this->channel->id));
    }

    public function test_prevent_overselling_with_sufficient_quantity(): void
    {
        $inventory = ChannelInventory::factory()->create([
            'channel_id' => $this->channel->id,
            'product_id' => 'PROD-001',
            'available_qty' => 10,
        ]);

        $this->assertTrue($this->inventoryService->preventOverselling('PROD-001', 5, $this->channel->id));
    }

    public function test_reserve_inventory(): void
    {
        $inventory = ChannelInventory::factory()->create([
            'channel_id' => $this->channel->id,
            'product_id' => 'PROD-001',
            'available_qty' => 10,
            'reserved_qty' => 0,
        ]);

        $success = $this->inventoryService->reserveInventory($this->channel, 'PROD-001', 5);

        $this->assertTrue($success);
        $inventory->refresh();
        $this->assertEquals(5, $inventory->available_qty);
        $this->assertEquals(5, $inventory->reserved_qty);
    }

    public function test_release_inventory(): void
    {
        $inventory = ChannelInventory::factory()->create([
            'channel_id' => $this->channel->id,
            'product_id' => 'PROD-001',
            'available_qty' => 5,
            'reserved_qty' => 5,
        ]);

        $this->inventoryService->releaseInventory($this->channel, 'PROD-001', 3);

        $inventory->refresh();
        $this->assertEquals(8, $inventory->available_qty);
        $this->assertEquals(2, $inventory->reserved_qty);
    }

    public function test_get_inventory_status(): void
    {
        ChannelInventory::factory()->count(3)->create(['channel_id' => $this->channel->id]);

        $status = $this->inventoryService->getInventoryStatus($this->channel->id);

        $this->assertEquals($this->channel->id, $status['channel_id']);
        $this->assertEquals(3, $status['total_items']);
        $this->assertIsArray($status['items']);
    }

    public function test_push_inventory_update(): void
    {
        $result = $this->inventoryService->pushInventoryUpdate($this->channel, 'PROD-001', 25);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(25, $result['quantity']);

        $inventory = ChannelInventory::where('channel_id', $this->channel->id)
            ->where('product_id', 'PROD-001')
            ->first();

        $this->assertNotNull($inventory);
        $this->assertEquals(25, $inventory->available_qty);
    }

    public function test_pull_inventory_state(): void
    {
        ChannelInventory::factory()->count(2)->create(['channel_id' => $this->channel->id]);

        $state = $this->inventoryService->pullInventoryState($this->channel);

        $this->assertEquals($this->channel->id, $state['channel_id']);
        $this->assertEquals(2, $state['inventory_count']);
        $this->assertIsArray($state['inventory']);
    }
}
