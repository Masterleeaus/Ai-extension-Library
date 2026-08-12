<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Mapping;

use RuntimeException;

final class MappingValidationException extends RuntimeException
{
    /** @param array<int, string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Canonical mapping validation failed: ' . implode('; ', $errors));
    }
}
