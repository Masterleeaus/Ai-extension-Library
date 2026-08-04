<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Contracts;

interface ModelInferenceClient
{
    /** @return array{data:array,meta:array} */
    public function structured(string $model, array $input, array $schema, array $options = []): array;
}
