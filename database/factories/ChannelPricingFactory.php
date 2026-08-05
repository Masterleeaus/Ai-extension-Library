<?php

namespace Database\Factories;

use App\Domains\WorkCore\System\Modules\Channels\Models\{Channel, ChannelPricing};
use Illuminate\Database\Eloquent\Factories\Factory;

class ChannelPricingFactory extends Factory
{
    protected $model = ChannelPricing::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'channel_id' => Channel::factory(),
            'product_id' => 'PROD-' . $this->faker->uuid(),
            'channel_price' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'USD',
            'local_price' => $this->faker->randomFloat(2, 10, 1000),
            'last_sync' => now(),
        ];
    }

    public function outOfSync(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'last_sync' => now()->subHours(2),
            ];
        });
    }

    public function withPricingRules(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'pricing_rules' => [
                    'markup_percentage' => 15,
                    'minimum_price' => 10,
                ],
            ];
        });
    }
}
