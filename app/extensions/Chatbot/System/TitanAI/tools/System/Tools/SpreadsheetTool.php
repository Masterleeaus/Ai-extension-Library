<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class SpreadsheetTool extends AbstractTool
{
    public static function id(): string { return 'spreadsheet'; }
    public static function name(): string { return 'Spreadsheet Tool'; }
    public static function operation(): string { return 'Read or write an approved spreadsheet operation'; }
    public static function requiresApproval(): bool { return true; }
    public static function permissions(): array { return ['spreadsheets.execute']; }
}
