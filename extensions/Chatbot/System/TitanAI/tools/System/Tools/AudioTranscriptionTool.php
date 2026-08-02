<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class AudioTranscriptionTool extends AbstractTool
{
    public static function id(): string { return 'audio-transcription'; }
    public static function name(): string { return 'Audio Transcription Tool'; }
    public static function operation(): string { return 'Transcribe an audio recording'; }
    public static function requiresApproval(): bool { return false; }
    public static function permissions(): array { return ['audio.transcribe']; }
}
