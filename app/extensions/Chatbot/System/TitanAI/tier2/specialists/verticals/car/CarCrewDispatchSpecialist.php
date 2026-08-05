<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Car;
class CarCrewDispatchSpecialist {
    public function process(array $c): array { return ['specialist' => 'CarCrewDispatchSpecialist', 'vertical' => 'car', 'status' => 'processing', 'intent' => $c['intent'] ?? '']; }
}
