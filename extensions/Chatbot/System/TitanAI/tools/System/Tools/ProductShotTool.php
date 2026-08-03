<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier4\Tools;

final class ProductShotTool
{
    public const DESCRIPTION = 'Creates a product or equipment shot from supplied assets and instructions.';
    public function id(): string { return 'product-shot-tool'; }
    public function authority(): string { return 'workcore-only'; }
}
