<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Vertical\Contracts;

use TitanZero\Interaction\Vertical\DTO\VerticalContextLayer;

interface VerticalContextProviderInterface
{
    public function layer(array $input): VerticalContextLayer;
}
