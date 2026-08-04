<?php
namespace App\Services\AI\Tier2\Car;
class CarJobCompletionSpecialist {
    public function process(array $c): array { return ['specialist' => 'CarJobCompletionSpecialist', 'vertical' => 'car', 'status' => 'processing', 'intent' => $c['intent'] ?? '']; }
}
