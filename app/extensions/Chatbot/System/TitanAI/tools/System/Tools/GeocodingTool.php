<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class GeocodingTool extends AbstractTool
{
    public static function id(): string { return 'geocoding'; }
    public static function name(): string { return 'Geocoding Tool'; }
    public static function operation(): string { return 'Resolve an address or coordinate'; }
    public static function requiresApproval(): bool { return false; }
    public static function permissions(): array { return ['locations.geocode']; }
}
