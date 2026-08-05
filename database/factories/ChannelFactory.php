<?php

namespace Database\Factories;

use App\Domains\WorkCore\System\Modules\Channels\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'name' => $this->faker->words(2, true),
            'type' => $this->faker->randomElement(['airbnb', 'booking', 'amazon', 'ebay', 'delivery']),
            'api_token' => 'test_' . $this->faker->uuid(),
            'api_secret' => 'secret_' . $this->faker->uuid(),
            'enabled' => true,
            'settings' => null,
            'auth_status' => 'pending',
        ];
    }

    public function authenticated(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'auth_status' => 'authenticated',
                'authenticated_at' => now(),
            ];
        });
    }

    public function disabled(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'enabled' => false,
            ];
        });
    }

    public function airbnb(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'airbnb',
            ];
        });
    }

    public function booking(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'booking',
            ];
        });
    }

    public function amazon(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'amazon',
            ];
        });
    }

    public function ebay(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'ebay',
            ];
        });
    }
}
