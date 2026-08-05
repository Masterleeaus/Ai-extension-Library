<?php

namespace Database\Factories;

use App\Domains\WorkCore\System\Modules\Channels\Models\{Channel, ChannelOrder};
use Illuminate\Database\Eloquent\Factories\Factory;

class ChannelOrderFactory extends Factory
{
    protected $model = ChannelOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'channel_id' => Channel::factory(),
            'order_id_remote' => 'ORD-' . $this->faker->uuid(),
            'local_order_id' => 'LOCAL-' . $this->faker->uuid(),
            'status' => 'synced',
            'order_data' => [
                'customer' => $this->faker->name(),
                'total' => $this->faker->randomFloat(2, 10, 1000),
            ],
            'synced_at' => now(),
        ];
    }

    public function pending(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'synced_at' => null,
            ];
        });
    }

    public function withError(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'error',
                'error_message' => 'Failed to sync order',
            ];
        });
    }
}
