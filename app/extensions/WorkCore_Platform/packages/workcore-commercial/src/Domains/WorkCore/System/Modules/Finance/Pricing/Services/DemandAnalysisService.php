<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Services;

final class DemandAnalysisService
{
    /** @param array<string,mixed> $signals */
    public function score(array $signals): float
    {
        $bookings = max(0.0, (float) ($signals['bookings'] ?? 0));
        $searches = max(0.0, (float) ($signals['searches'] ?? 0));
        $inquiries = max(0.0, (float) ($signals['inquiries'] ?? 0));
        $conversion = max(0.0, min(1.0, (float) ($signals['conversion_rate'] ?? 0)));
        $normalizer = max(1.0, (float) ($signals['normalizer'] ?? 100));
        $weighted = ($bookings * 0.50) + ($inquiries * 0.30) + ($searches * 0.20);
        return round(max(0, min(100, (($weighted / $normalizer) * 100) + ($conversion * 10))), 2);
    }
}
