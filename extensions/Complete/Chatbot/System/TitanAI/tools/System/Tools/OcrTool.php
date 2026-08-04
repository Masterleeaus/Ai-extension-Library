<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class OcrTool extends AbstractTool
{
    public static function id(): string { return 'ocr'; }
    public static function name(): string { return 'OCR Tool'; }
    public static function operation(): string { return 'Extract text from an image or document'; }
    public static function requiresApproval(): bool { return false; }
    public static function permissions(): array { return ['documents.ocr']; }
}
