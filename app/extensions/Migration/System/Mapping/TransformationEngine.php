<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Mapping;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class TransformationEngine
{
    public function __construct(private readonly TransformationRegistry $registry)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $steps
     * @param array<string, mixed> $source
     * @return array{value:mixed,audit:array<int, array<string, string>>}
     */
    public function apply(mixed $value, array $steps, array $source = []): array
    {
        $audit = [];
        $current = $value;

        foreach ($steps as $step) {
            if (! is_array($step)) {
                throw new InvalidArgumentException('Transformation step must be an object-like array.');
            }
            $key = (string) ($step['transform'] ?? '');
            $this->registry->get($key);
            $config = $step['config'] ?? [];
            if (! is_array($config)) {
                throw new InvalidArgumentException("Transformation {$key} config must be an array.");
            }

            $before = $current;
            $current = $this->execute($key, $current, $config, $source);
            $audit[] = [
                'transform' => $key,
                'before_digest' => $this->digest($before),
                'after_digest' => $this->digest($current),
            ];
        }

        return ['value' => $current, 'audit' => $audit];
    }

    /** @param array<string, mixed> $config
     *  @param array<string, mixed> $source
     */
    private function execute(string $key, mixed $value, array $config, array $source): mixed
    {
        return match ($key) {
            'trim' => is_string($value) ? trim($value) : $value,
            'lowercase' => $this->lowercase($value),
            'uppercase' => $this->uppercase($value),
            'string' => $value === null ? null : (string) $value,
            'integer' => $this->integer($value),
            'number' => $this->number($value),
            'boolean' => $this->boolean($value),
            'coalesce' => $this->coalesce($value, $config, $source),
            'concat' => $this->concat($config, $source),
            'map_values' => $this->mapValues($value, $config),
            'regex_replace' => $this->regexReplace($value, $config),
            'date_format' => $this->dateFormat($value, $config),
            default => throw new InvalidArgumentException("Unknown transformation: {$key}"),
        };
    }

    private function lowercase(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function uppercase(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }

    private function integer(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('integer transformation requires numeric input.');
        }

        return (int) $value;
    }

    private function number(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('number transformation requires numeric input.');
        }

        return (float) $value;
    }

    private function boolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value !== 0.0;
        }

        return match (strtolower(trim((string) $value))) {
            '1', 'true', 'yes', 'y', 'on', 'active', 'enabled' => true,
            '0', 'false', 'no', 'n', 'off', 'inactive', 'disabled' => false,
            default => throw new InvalidArgumentException('boolean transformation received an unknown value.'),
        };
    }

    /** @param array<string, mixed> $config
     *  @param array<string, mixed> $source
     */
    private function coalesce(mixed $value, array $config, array $source): mixed
    {
        if (! $this->blank($value)) {
            return $value;
        }

        $fields = is_array($config['fields'] ?? null) ? $config['fields'] : [];
        foreach ($fields as $field) {
            if (is_string($field) && array_key_exists($field, $source) && ! $this->blank($source[$field])) {
                return $source[$field];
            }
        }

        return $config['default'] ?? null;
    }

    /** @param array<string, mixed> $config
     *  @param array<string, mixed> $source
     */
    private function concat(array $config, array $source): string
    {
        $fields = is_array($config['fields'] ?? null) ? $config['fields'] : [];
        if ($fields === []) {
            throw new InvalidArgumentException('concat transformation requires fields.');
        }
        $separator = (string) ($config['separator'] ?? ' ');
        $parts = [];
        foreach ($fields as $field) {
            if (! is_string($field)) {
                throw new InvalidArgumentException('concat fields must be strings.');
            }
            $candidate = $source[$field] ?? null;
            if (! $this->blank($candidate)) {
                $parts[] = (string) $candidate;
            }
        }

        return (string) ($config['prefix'] ?? '') . implode($separator, $parts) . (string) ($config['suffix'] ?? '');
    }

    /** @param array<string, mixed> $config */
    private function mapValues(mixed $value, array $config): mixed
    {
        $map = $config['map'] ?? null;
        if (! is_array($map)) {
            throw new InvalidArgumentException('map_values transformation requires a map array.');
        }
        $key = is_scalar($value) || $value === null ? (string) $value : null;
        if ($key !== null && array_key_exists($key, $map)) {
            return $map[$key];
        }

        return array_key_exists('default', $config) ? $config['default'] : $value;
    }

    /** @param array<string, mixed> $config */
    private function regexReplace(mixed $value, array $config): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        $pattern = (string) ($config['pattern'] ?? '');
        if ($pattern === '' || @preg_match($pattern, '') === false) {
            throw new InvalidArgumentException('regex_replace transformation requires a valid pattern.');
        }
        $replacement = (string) ($config['replacement'] ?? '');
        $result = preg_replace($pattern, $replacement, $value);
        if ($result === null) {
            throw new InvalidArgumentException('regex_replace transformation failed.');
        }

        return $result;
    }

    /** @param array<string, mixed> $config */
    private function dateFormat(mixed $value, array $config): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_scalar($value)) {
            throw new InvalidArgumentException('date_format transformation requires scalar input.');
        }
        $output = (string) ($config['output_format'] ?? 'Y-m-d\TH:i:sP');
        $timezone = new DateTimeZone((string) ($config['timezone'] ?? 'UTC'));
        $input = $config['input_format'] ?? null;
        $date = is_string($input) && $input !== ''
            ? DateTimeImmutable::createFromFormat($input, (string) $value, $timezone)
            : new DateTimeImmutable((string) $value, $timezone);
        if ($date === false) {
            throw new InvalidArgumentException('date_format transformation could not parse input.');
        }

        return $date->setTimezone($timezone)->format($output);
    }

    private function blank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', serialize($value));
    }
}
