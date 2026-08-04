<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier4\Tools;

final class SeoAnalysisTool
{
    public const DESCRIPTION = 'Performs SEO analysis without operational record authority.';

    public function id(): string
    {
        return 'seo-analysis-tool';
    }

    public function authority(): string
    {
        return 'workcore-only';
    }
}
