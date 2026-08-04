<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class FileStorageTool extends AbstractTool
{
    public static function id(): string { return 'file-storage'; }
    public static function name(): string { return 'File Storage Tool'; }
    public static function operation(): string { return 'Store an authorised file'; }
    public static function requiresApproval(): bool { return true; }
    public static function permissions(): array { return ['files.create']; }
}
