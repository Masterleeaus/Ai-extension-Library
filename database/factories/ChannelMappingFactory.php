<?php

namespace Database\Factories;

use App\Domains\WorkCore\System\Modules\Channels\Models\{Channel, ChannelMapping};
use Illuminate\Database\Eloquent\Factories\Factory;

class ChannelMappingFactory extends Factory
{
    protected $model = ChannelMapping::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'channel_id' => Channel::factory(),
            'local_id' => 'LOCAL-' . $this->faker->uuid(),
            'channel_id_remote' => 'REMOTE-' . $this->faker->uuid(),
            'sync_status' => 'synced',
            'last_sync_at' => now(),
        ];
    }

    public function pending(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'sync_status' => 'pending',
                'last_sync_at' => null,
            ];
        });
    }

    public function withError(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'sync_status' => 'error',
                'error_message' => 'Test error occurred',
            ];
        });
    }
}
