<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools\Contracts;

interface ToolContract
{
    public static function id(): string;
    public static function name(): string;
    public static function operation(): string;
    public static function requiresApproval(): bool;
    public static function permissions(): array;
}
