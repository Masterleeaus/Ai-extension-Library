<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class VisionAnalysisTool extends AbstractTool
{
    public static function id(): string { return 'vision-analysis'; }
    public static function name(): string { return 'Vision Analysis Tool'; }
    public static function operation(): string { return 'Analyse cleaning image evidence'; }
    public static function requiresApproval(): bool { return false; }
    public static function permissions(): array { return ['vision.analyse']; }
}
