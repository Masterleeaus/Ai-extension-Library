<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class CalendarAvailabilityTool extends AbstractTool
{
    public static function id(): string { return 'calendar-availability'; }
    public static function name(): string { return 'Calendar Availability Tool'; }
    public static function operation(): string { return 'Read calendar availability'; }
    public static function requiresApproval(): bool { return false; }
    public static function permissions(): array { return ['calendar.availability.read']; }
}
