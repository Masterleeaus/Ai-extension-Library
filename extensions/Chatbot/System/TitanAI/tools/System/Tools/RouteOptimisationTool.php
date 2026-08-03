<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class RouteOptimisationTool extends AbstractTool
{
    public static function id(): string { return 'route-optimisation'; }
    public static function name(): string { return 'Route Optimisation Tool'; }
    public static function operation(): string { return 'Calculate an efficient job route'; }
    public static function requiresApproval(): bool { return false; }
    public static function permissions(): array { return ['routes.optimise']; }
}
