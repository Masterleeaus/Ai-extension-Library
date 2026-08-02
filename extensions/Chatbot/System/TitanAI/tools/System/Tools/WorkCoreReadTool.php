<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class WorkCoreReadTool extends AbstractTool
{
    public static function id(): string { return 'workcore-read'; }
    public static function name(): string { return 'WorkCore Read Tool'; }
    public static function operation(): string { return 'Read an authorised WorkCore projection'; }
    public static function requiresApproval(): bool { return false; }
    public static function permissions(): array { return ['workcore.read_models.read']; }
}
