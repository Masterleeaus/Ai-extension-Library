<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class VoiceSynthesisTool extends AbstractTool
{
    public static function id(): string { return 'voice-synthesis'; }
    public static function name(): string { return 'Voice Synthesis Tool'; }
    public static function operation(): string { return 'Synthesize an approved spoken response'; }
    public static function requiresApproval(): bool { return false; }
    public static function permissions(): array { return ['audio.synthesise']; }
}
