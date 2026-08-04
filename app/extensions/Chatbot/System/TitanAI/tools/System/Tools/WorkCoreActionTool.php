<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class WorkCoreActionTool extends AbstractTool
{
    public static function id(): string { return 'workcore-action'; }
    public static function name(): string { return 'WorkCore Action Tool'; }
    public static function operation(): string { return 'Execute an authorised WorkCore business action'; }
    public static function requiresApproval(): bool { return true; }
    public static function permissions(): array { return ['workcore.actions.execute']; }
}
