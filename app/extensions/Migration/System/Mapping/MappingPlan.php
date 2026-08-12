<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Mapping;

use InvalidArgumentException;

final readonly class MappingPlan
{
    /** @param array<int, MappingRule> $rules */
    public function __construct(public array $rules)
    {
        $targets = [];
        foreach ($this->rules as $rule) {
            if (! $rule instanceof MappingRule) {
                throw new InvalidArgumentException('Mapping plan accepts MappingRule instances only.');
            }
            if (isset($targets[$rule->targetField])) {
                throw new InvalidArgumentException("Target field {$rule->targetField} is mapped more than once.");
            }
            $targets[$rule->targetField] = true;
        }
    }
}
