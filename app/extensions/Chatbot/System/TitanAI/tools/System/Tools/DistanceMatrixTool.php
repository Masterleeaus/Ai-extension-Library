<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class DistanceMatrixTool extends AbstractTool
{
    public static function id(): string { return 'distance-matrix'; }
    public static function name(): string { return 'Distance Matrix Tool'; }
    public static function operation(): string { return 'Calculate travel distance and duration'; }
    public static function requiresApproval(): bool { return false; }
    public static function permissions(): array { return ['routes.distance.read']; }
}
