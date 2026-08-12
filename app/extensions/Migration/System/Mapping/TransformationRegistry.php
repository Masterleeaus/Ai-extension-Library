<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Mapping;

use InvalidArgumentException;

final class TransformationRegistry
{
    /** @var array<string, TransformationDefinition> */
    private array $definitions = [];

    public function __construct()
    {
        foreach ([
            ['trim', 'Trim surrounding whitespace.'],
            ['lowercase', 'Convert text to lowercase.'],
            ['uppercase', 'Convert text to uppercase.'],
            ['string', 'Convert scalar input to string.'],
            ['integer', 'Convert numeric input to integer.'],
            ['number', 'Convert numeric input to floating point.'],
            ['boolean', 'Convert common boolean representations to boolean.'],
            ['coalesce', 'Use configured source fields/default when the current value is blank.'],
            ['concat', 'Concatenate configured source fields.'],
            ['map_values', 'Map exact source values through a declared lookup table.'],
            ['regex_replace', 'Apply a declared regular-expression replacement.'],
            ['date_format', 'Parse and format a date using declared formats.'],
        ] as [$key, $description]) {
            $this->register(new TransformationDefinition($key, $description));
        }
    }

    public function register(TransformationDefinition $definition): void
    {
        if (isset($this->definitions[$definition->key])) {
            throw new InvalidArgumentException("Transformation {$definition->key} is already registered.");
        }
        $this->definitions[$definition->key] = $definition;
        ksort($this->definitions);
    }

    public function get(string $key): TransformationDefinition
    {
        return $this->definitions[$key]
            ?? throw new InvalidArgumentException("Unknown transformation: {$key}");
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    /** @return array<string, TransformationDefinition> */
    public function all(): array
    {
        return $this->definitions;
    }
}
