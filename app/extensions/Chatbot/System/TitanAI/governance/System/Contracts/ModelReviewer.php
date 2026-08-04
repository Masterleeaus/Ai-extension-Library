<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Contracts;

interface ModelReviewer
{
    /** @return array{decision:string,confidence:int,findings:array,evidence:array,model:string} */
    public function review(array $case): array;
}
