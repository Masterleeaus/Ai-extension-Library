<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Discovery;

final class TypeInferrer
{
    public function infer(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'null';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1)) {
            return 'integer';
        }

        if (is_float($value) || (is_string($value) && is_numeric(trim($value)))) {
            return 'number';
        }

        if (is_array($value) || is_object($value)) {
            return 'json';
        }

        if (is_string($value) && $this->looksLikeDateTime($value)) {
            return 'datetime';
        }

        return 'string';
    }

    public function merge(?string $current, string $observed): string
    {
        if ($current === null || $current === 'null') {
            return $observed;
        }

        if ($observed === 'null' || $observed === $current) {
            return $current;
        }

        if (in_array($current, ['integer', 'number'], true) && in_array($observed, ['integer', 'number'], true)) {
            return 'number';
        }

        return 'string';
    }

    private function looksLikeDateTime(string $value): bool
    {
        $trimmed = trim($value);
        if ($trimmed === '' || strlen($trimmed) < 8 || ! preg_match('/[-:\/T]/', $trimmed)) {
            return false;
        }

        $timestamp = strtotime($trimmed);

        return $timestamp !== false;
    }
}
