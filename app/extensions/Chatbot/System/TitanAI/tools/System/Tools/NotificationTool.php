<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class NotificationTool extends AbstractTool
{
    public static function id(): string { return 'notification'; }
    public static function name(): string { return 'Notification Tool'; }
    public static function operation(): string { return 'Dispatch a governed notification'; }
    public static function requiresApproval(): bool { return true; }
    public static function permissions(): array { return ['notifications.send']; }
}
