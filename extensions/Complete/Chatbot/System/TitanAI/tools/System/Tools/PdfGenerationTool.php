<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class PdfGenerationTool extends AbstractTool
{
    public static function id(): string { return 'pdf-generation'; }
    public static function name(): string { return 'PDF Generation Tool'; }
    public static function operation(): string { return 'Generate a PDF document'; }
    public static function requiresApproval(): bool { return false; }
    public static function permissions(): array { return ['documents.generate']; }
}
