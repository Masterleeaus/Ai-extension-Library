<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class WeatherTool extends AbstractTool
{
    public static function id(): string { return 'weather'; }
    public static function name(): string { return 'Weather Tool'; }
    public static function operation(): string { return 'Read weather conditions relevant to field work'; }
    public static function requiresApproval(): bool { return false; }
    public static function permissions(): array { return ['weather.read']; }
}
