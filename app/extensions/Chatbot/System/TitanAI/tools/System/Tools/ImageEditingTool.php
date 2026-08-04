<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier4\Tools;

final class ImageEditingTool
{
    public const DESCRIPTION = 'Edits an image through a configured governed image provider.';
    public function id(): string { return 'image-editing-tool'; }
    public function authority(): string { return 'workcore-only'; }
}
