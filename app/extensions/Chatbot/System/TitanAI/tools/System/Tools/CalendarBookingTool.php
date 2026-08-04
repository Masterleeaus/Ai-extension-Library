<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class CalendarBookingTool extends AbstractTool
{
    public static function id(): string { return 'calendar-booking'; }
    public static function name(): string { return 'Calendar Booking Tool'; }
    public static function operation(): string { return 'Write one approved calendar booking'; }
    public static function requiresApproval(): bool { return true; }
    public static function permissions(): array { return ['calendar.events.create']; }
}
