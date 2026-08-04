<?php
namespace App\Services\AI\Tier2\Car;
class CarJobStatusSpecialist {
    public function process(array $c): array { return ['specialist' => 'CarJobStatusSpecialist', 'vertical' => 'car', 'status' => 'processing', 'intent' => $c['intent'] ?? '']; }
}
