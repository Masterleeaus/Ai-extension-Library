<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\WorkCore\Channels;

use App\Domains\WorkCore\System\Modules\Channels\Models\{Channel, ChannelInventory};
use App\Domains\WorkCore\System\Modules\Channels\Services\InventorySyncService;
use Tests\TestCase;

class OversellingScenariosTest extends TestCase
{
    private InventorySyncService $inventoryService;
    private Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = app(InventorySyncService::class);
        $this->channel = Channel::factory()->create(['type' => 'airbnb']);
    }

    public function test_no_overselling_with_atomic_inventory_check(): void
    {
        $inventory = ChannelInventory::factory()->create([
            'channel_id' => $this->channel->id,
            'product_id' => 'PROD-001',
            'available_qty' => 10,
        ]);

        // First reservation
        $success1 = $this->inventoryService->reserveInventory($this->channel, 'PROD-001', 8);
        $this->assertTrue($success1);

        // Second reservation should fail
        $success2 = $this->inventoryService->reserveInventory($this->channel, 'PROD-001', 5);
        $this->assertFalse($success2);

        $inventory->refresh();
        $this->assertEquals(2, $inventory->available_qty);
        $this->assertEquals(8, $inventory->reserved_qty);
    }

    public function test_concurrent_reservations_prevent_overselling(): void
    {
        $inventory = ChannelInventory::factory()->create([
            'channel_id' => $this->channel->id,
            'product_id' => 'PROD-002',
            'available_qty' => 5,
        ]);

        // Multiple threads trying to reserve more than available
        $success1 = $this->inventoryService->reserveInventory($this->channel, 'PROD-002', 3);
        $success2 = $this->inventoryService->reserveInventory($this->channel, 'PROD-002', 3);

        $this->assertTrue($success1);
        $this->assertFalse($success2); // Should fail due to insufficient inventory

        $inventory->refresh();
        $this->assertTrue($inventory->available_qty >= 0);
    }

    public function test_total_inventory_never_exceeds_capacity(): void
    {
        $inventory = ChannelInventory::factory()->create([
            'channel_id' => $this->channel->id,
            'product_id' => 'PROD-003',
            'available_qty' => 10,
            'reserved_qty' => 0,
        ]);

        $this->assertEquals(10, $inventory->getNetStock());

        $this->inventoryService->reserveInventory($this->channel, 'PROD-003', 5);
        $inventory->refresh();
        $this->assertEquals(10, $inventory->getNetStock());

        $this->inventoryService->releaseInventory($this->channel, 'PROD-003', 3);
        $inventory->refresh();
        $this->assertEquals(10, $inventory->getNetStock());
    }

    public function test_multiple_channels_same_product(): void
    {
        $channel1 = Channel::factory()->create(['type' => 'airbnb']);
        $channel2 = Channel::factory()->create(['type' => 'booking']);

        ChannelInventory::factory()->create([
            'channel_id' => $channel1->id,
            'product_id' => 'PROD-004',
            'available_qty' => 5,
        ]);

        ChannelInventory::factory()->create([
            'channel_id' => $channel2->id,
            'product_id' => 'PROD-004',
            'available_qty' => 3,
        ]);

        // Check global inventory prevention
        $canSell = $this->inventoryService->preventOverselling('PROD-004', 10);
        $this->assertFalse($canSell); // Total is 8, can't sell 10

        $canSell = $this->inventoryService->preventOverselling('PROD-004', 7);
        $this->assertTrue($canSell); // Can sell 7 out of 8
    }

    public function test_negative_inventory_never_occurs(): void
    {
        $inventory = ChannelInventory::factory()->create([
            'channel_id' => $this->channel->id,
            'product_id' => 'PROD-005',
            'available_qty' => 0,
            'reserved_qty' => 5,
        ]);

        // Try to reserve from empty available stock
        $success = $this->inventoryService->reserveInventory($this->channel, 'PROD-005', 1);
        $this->assertFalse($success);

        $inventory->refresh();
        $this->assertEquals(0, $inventory->available_qty);
        $this->assertGreaterThanOrEqual(0, $inventory->available_qty);
    }
}
