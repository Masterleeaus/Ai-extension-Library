<?php
namespace App\Services\AI\Tier2\Car;
class CarBillingSpecialist {
    public function process(array $c): array { return ['specialist' => 'CarBillingSpecialist', 'vertical' => 'car', 'status' => 'processing', 'intent' => $c['intent'] ?? '']; }
}
