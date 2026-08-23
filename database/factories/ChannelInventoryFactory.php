<?php

namespace Database\Factories;

use App\Domains\WorkCore\System\Modules\Channels\Models\{Channel, ChannelInventory};
use Illuminate\Database\Eloquent\Factories\Factory;

class ChannelInventoryFactory extends Factory
{
    protected $model = ChannelInventory::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'channel_id' => Channel::factory(),
            'product_id' => 'PROD-' . $this->faker->uuid(),
            'available_qty' => $this->faker->numberBetween(0, 100),
            'reserved_qty' => 0,
            'channel_qty' => $this->faker->numberBetween(0, 100),
            'last_sync' => now(),
        ];
    }

    public function outOfStock(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'available_qty' => 0,
                'reserved_qty' => 0,
            ];
        });
    }

    public function lowStock(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'available_qty' => $this->faker->numberBetween(1, 4),
            ];
        });
    }
}
