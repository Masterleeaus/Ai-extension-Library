<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier4\Tools;

final class PhotoShootTool
{
    public const DESCRIPTION = 'Creates a staged image set from supplied assets and instructions.';
    public function id(): string { return 'photo-shoot-tool'; }
    public function authority(): string { return 'workcore-only'; }
}
