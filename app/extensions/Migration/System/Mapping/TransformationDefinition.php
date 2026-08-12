<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Mapping;

use InvalidArgumentException;

final readonly class TransformationDefinition
{
    public function __construct(
        public string $key,
        public string $description,
    ) {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $this->key) !== 1) {
            throw new InvalidArgumentException('Transformation key must be a lowercase identifier.');
        }
        if (trim($this->description) === '') {
            throw new InvalidArgumentException('Transformation description cannot be empty.');
        }
    }
}
