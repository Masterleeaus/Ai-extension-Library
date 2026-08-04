<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier4\Tools;

final class ImageGenerationTool
{
    public const DESCRIPTION = 'Generates an image through a configured governed image provider.';
    public function id(): string { return 'image-generation-tool'; }
    public function authority(): string { return 'workcore-only'; }
}
