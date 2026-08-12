<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Canonical\Contracts;

use App\Extensions\Migration\System\Canonical\CanonicalEntityDefinition;

interface CanonicalEntityPackInterface
{
    /** @return array<int, CanonicalEntityDefinition> */
    public function definitions(): array;
}
