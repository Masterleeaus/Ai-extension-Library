<?php
namespace App\Services\AI\Tier2\Car;
class CarJobSchedulingSpecialist {
    public function process(array $c): array { return ['specialist' => 'CarJobSchedulingSpecialist', 'vertical' => 'car', 'status' => 'processing', 'intent' => $c['intent'] ?? '']; }
}
