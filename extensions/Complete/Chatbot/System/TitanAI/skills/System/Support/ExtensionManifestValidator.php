<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Support;

final class ExtensionManifestValidator
{
    private const REQUIRED_FIELDS = [
        'name',
        'key',
        'type',
        'version',
        'contract_version',
        'description',
        'entry_provider',
        'requires',
        'capabilities',
        'permission_contract',
    ];

    private const SUPPORTED_CONTRACT_VERSIONS = ['1.0'];

    private const SUPPORTED_PERMISSION_CONTRACTS = ['1.0'];

    /**
     * @param array<string, mixed> $manifest
     * @return list<string>
     */
    public static function validate(array $manifest): array
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (! array_key_exists($field, $manifest)) {
                $errors[] = "Missing required field: {$field}.";
            }
        }

        if ($errors !== []) {
            return $errors;
        }

        if (! self::isNonEmptyString($manifest['name'])) {
            $errors[] = 'Field name must be a non-empty string.';
        }

        if (! is_string($manifest['key']) || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $manifest['key']) !== 1) {
            $errors[] = 'Field key must contain only lowercase letters, numbers, and hyphens.';
        }

        if ($manifest['type'] !== 'extension') {
            $errors[] = 'Field type must equal extension.';
        }

        if (! is_string($manifest['version']) || preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $manifest['version']) !== 1) {
            $errors[] = 'Field version must be a semantic version such as 1.0.0.';
        }

        if (! is_string($manifest['contract_version']) || ! in_array($manifest['contract_version'], self::SUPPORTED_CONTRACT_VERSIONS, true)) {
            $errors[] = 'Field contract_version must be one of: ' . implode(', ', self::SUPPORTED_CONTRACT_VERSIONS) . '.';
        }

        if (! self::isNonEmptyString($manifest['description'])) {
            $errors[] = 'Field description must be a non-empty string.';
        }

        if (! self::isSafeRelativePath($manifest['entry_provider'])) {
            $errors[] = 'Field entry_provider must be a safe non-empty relative path.';
        }

        foreach (['requires', 'capabilities'] as $field) {
            self::validateStringList($manifest, $field, $errors);
        }

        if (! is_string($manifest['permission_contract']) || ! in_array($manifest['permission_contract'], self::SUPPORTED_PERMISSION_CONTRACTS, true)) {
            $errors[] = 'Field permission_contract must be one of: ' . implode(', ', self::SUPPORTED_PERMISSION_CONTRACTS) . '.';
        }

        if (isset($manifest['compatibility']) && ! is_array($manifest['compatibility'])) {
            $errors[] = 'Field compatibility must be a JSON object.';
        }

        return $errors;
    }

    private static function isNonEmptyString(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private static function isSafeRelativePath(mixed $value): bool
    {
        if (! self::isNonEmptyString($value)) {
            return false;
        }

        $path = str_replace('\\', '/', trim((string) $value));

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\//', $path) === 1) {
            return false;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $manifest
     * @param list<string> $errors
     */
    private static function validateStringList(array $manifest, string $field, array &$errors): void
    {
        $value = $manifest[$field];

        if (! is_array($value) || ! array_is_list($value)) {
            $errors[] = "Field {$field} must be a JSON list.";
            return;
        }

        foreach ($value as $item) {
            if (! self::isNonEmptyString($item)) {
                $errors[] = "Field {$field} may contain only non-empty strings.";
                return;
            }
        }

        if (count($value) !== count(array_unique($value, SORT_STRING))) {
            $errors[] = "Field {$field} must not contain duplicate values.";
        }
    }
}
