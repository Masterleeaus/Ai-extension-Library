<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Mapping;

use InvalidArgumentException;

final readonly class MappingRule
{
    /** @param array<int, array<string, mixed>> $steps */
    public function __construct(
        public string $sourceField,
        public string $targetField,
        public array $steps = [],
        public mixed $defaultValue = null,
    ) {
        if (trim($this->sourceField) === '' || trim($this->targetField) === '') {
            throw new InvalidArgumentException('Mapping source and target fields cannot be empty.');
        }
        foreach ($this->steps as $step) {
            if (! is_array($step) || trim((string) ($step['transform'] ?? '')) === '') {
                throw new InvalidArgumentException('Mapping transformation steps must declare a transform key.');
            }
        }
    }
}
