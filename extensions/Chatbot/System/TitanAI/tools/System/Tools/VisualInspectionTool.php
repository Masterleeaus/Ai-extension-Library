<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class VisualInspectionTool extends AbstractTool
{
    public static function id(): string { return 'visual-inspection'; }
    public static function name(): string { return 'Visual Inspection Tool'; }
    public static function operation(): string { return 'Run visual inspection analysis and comparison'; }
    public static function requiresApproval(): bool { return false; }
    public static function permissions(): array { return ['vision.inspect']; }
}
