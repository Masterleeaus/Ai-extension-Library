<?php

declare(strict_types=1);

namespace Foundation\Support;

use Throwable;

class InputValidator
{
    private const MAX_STRING_LENGTH = 10000;
    private const MIN_STRING_LENGTH = 1;
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    private const HEX_PATTERN = '/^[0-9a-f]+$/i';

    private static array $validationLog = [];

    /**
     * Validate tenant ID (UUID or hex format)
     *
     * @throws ValidationException
     */
    public static function validateTenantId(string $tenantId, ?string $fieldName = 'tenantId'): void
    {
        self::validateNonEmptyString($tenantId, $fieldName);

        if (!self::isValidTenantId($tenantId)) {
            self::logValidationFailure($fieldName, 'Invalid tenant ID format');
            throw new ValidationException("$fieldName must be a valid UUID or hex format");
        }
    }

    /**
     * Validate ID field (non-empty string, UUID or hex)
     *
     * @throws ValidationException
     */
    public static function validateId(string $id, ?string $fieldName = 'id'): void
    {
        self::validateNonEmptyString($id, $fieldName);

        if (!self::isValidId($id)) {
            self::logValidationFailure($fieldName, 'Invalid ID format');
            throw new ValidationException("$fieldName must be a valid UUID or hex format");
        }
    }

    /**
     * Validate non-empty string
     *
     * @throws ValidationException
     */
    public static function validateNonEmptyString(
        string $value,
        ?string $fieldName = 'field',
        int $maxLength = self::MAX_STRING_LENGTH,
        int $minLength = self::MIN_STRING_LENGTH
    ): void {
        if (empty($value)) {
            self::logValidationFailure($fieldName, 'Empty string provided');
            throw new ValidationException("$fieldName is required and cannot be empty");
        }

        if (strlen($value) < $minLength) {
            self::logValidationFailure($fieldName, "String too short (min: $minLength)");
            throw new ValidationException("$fieldName must be at least $minLength character(s)");
        }

        if (strlen($value) > $maxLength) {
            self::logValidationFailure($fieldName, "String too long (max: $maxLength)");
            throw new ValidationException("$fieldName must not exceed $maxLength characters");
        }
    }

    /**
     * Validate string with optional constraints
     *
     * @throws ValidationException
     */
    public static function validateString(
        string $value,
        ?string $fieldName = 'field',
        int $maxLength = self::MAX_STRING_LENGTH,
        int $minLength = 0,
        bool $nullable = false
    ): void {
        if (empty($value)) {
            if (!$nullable) {
                self::logValidationFailure($fieldName, 'Empty string provided (not nullable)');
                throw new ValidationException("$fieldName is required and cannot be empty");
            }
            return;
        }

        if (strlen($value) < $minLength) {
            self::logValidationFailure($fieldName, "String too short (min: $minLength)");
            throw new ValidationException("$fieldName must be at least $minLength character(s)");
        }

        if (strlen($value) > $maxLength) {
            self::logValidationFailure($fieldName, "String too long (max: $maxLength)");
            throw new ValidationException("$fieldName must not exceed $maxLength characters");
        }
    }

    /**
     * Validate array is not null and not empty (if required)
     *
     * @throws ValidationException
     */
    public static function validateArray(
        array $value,
        ?string $fieldName = 'array',
        bool $requireNonEmpty = false,
        int $maxItems = 10000
    ): void {
        if ($requireNonEmpty && empty($value)) {
            self::logValidationFailure($fieldName, 'Empty array provided (required non-empty)');
            throw new ValidationException("$fieldName cannot be empty");
        }

        if (count($value) > $maxItems) {
            self::logValidationFailure($fieldName, "Array too large (max: $maxItems items)");
            throw new ValidationException("$fieldName must not contain more than $maxItems items");
        }
    }

    /**
     * Validate numeric value is positive and within bounds
     *
     * @throws ValidationException
     */
    public static function validatePositiveInteger(
        int $value,
        ?string $fieldName = 'value',
        int $min = 1,
        int $max = PHP_INT_MAX
    ): void {
        if ($value < $min) {
            self::logValidationFailure($fieldName, "Value below minimum ($min)");
            throw new ValidationException("$fieldName must be at least $min");
        }

        if ($value > $max) {
            self::logValidationFailure($fieldName, "Value exceeds maximum ($max)");
            throw new ValidationException("$fieldName must not exceed $max");
        }
    }

    /**
     * Validate enum value is in allowed list
     *
     * @param string[] $allowedValues
     *
     * @throws ValidationException
     */
    public static function validateEnum(
        string $value,
        array $allowedValues,
        ?string $fieldName = 'field'
    ): void {
        self::validateNonEmptyString($value, $fieldName);

        if (!in_array($value, $allowedValues, true)) {
            self::logValidationFailure($fieldName, "Invalid enum value: $value");
            throw new ValidationException(
                "$fieldName must be one of: " . implode(', ', $allowedValues)
            );
        }
    }

    /**
     * Validate required fields in array
     *
     * @param string[] $requiredFields
     *
     * @throws ValidationException
     */
    public static function validateRequiredFields(
        array $data,
        array $requiredFields,
        ?string $fieldName = 'data'
    ): void {
        if (!is_array($data)) {
            self::logValidationFailure($fieldName, 'Data is not an array');
            throw new ValidationException("$fieldName must be an array");
        }

        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && empty($data[$field]))) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            self::logValidationFailure($fieldName, 'Missing required fields: ' . implode(', ', $missingFields));
            throw new ValidationException(
                "Missing required fields in $fieldName: " . implode(', ', $missingFields)
            );
        }
    }

    /**
     * Validate array item types
     *
     * @throws ValidationException
     */
    public static function validateArrayItemTypes(
        array $items,
        string $expectedType,
        ?string $fieldName = 'items'
    ): void {
        self::validateArray($items, $fieldName);

        $typeMap = [
            'string' => 'is_string',
            'int' => 'is_int',
            'float' => 'is_float',
            'bool' => 'is_bool',
            'array' => 'is_array',
        ];

        if (!isset($typeMap[$expectedType])) {
            throw new ValidationException("Unknown type: $expectedType");
        }

        $checker = $typeMap[$expectedType];
        foreach ($items as $index => $item) {
            if (!$checker($item)) {
                self::logValidationFailure($fieldName, "Item at index $index is not $expectedType");
                throw new ValidationException(
                    "$fieldName must contain only $expectedType values (item at index $index is " . gettype($item) . ")"
                );
            }
        }
    }

    /**
     * Validate boolean value
     *
     * @throws ValidationException
     */
    public static function validateBoolean(mixed $value, ?string $fieldName = 'field'): void
    {
        if (!is_bool($value)) {
            self::logValidationFailure($fieldName, "Value is not boolean");
            throw new ValidationException("$fieldName must be a boolean");
        }
    }

    /**
     * Validate URL format
     *
     * @throws ValidationException
     */
    public static function validateUrl(string $url, ?string $fieldName = 'url'): void
    {
        self::validateNonEmptyString($url, $fieldName, 2048);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            self::logValidationFailure($fieldName, 'Invalid URL format');
            throw new ValidationException("$fieldName must be a valid URL");
        }
    }

    /**
     * Validate email format
     *
     * @throws ValidationException
     */
    public static function validateEmail(string $email, ?string $fieldName = 'email'): void
    {
        self::validateNonEmptyString($email, $fieldName, 254);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::logValidationFailure($fieldName, 'Invalid email format');
            throw new ValidationException("$fieldName must be a valid email address");
        }
    }

    /**
     * Validate JSON string
     *
     * @throws ValidationException
     */
    public static function validateJson(string $json, ?string $fieldName = 'json'): void
    {
        self::validateNonEmptyString($json, $fieldName);

        try {
            json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            self::logValidationFailure($fieldName, 'Invalid JSON: ' . $e->getMessage());
            throw new ValidationException("$fieldName must be valid JSON");
        }
    }

    /**
     * Check if string is valid tenant ID
     */
    private static function isValidTenantId(string $tenantId): bool
    {
        // UUID format
        if (preg_match(self::UUID_PATTERN, $tenantId)) {
            return true;
        }

        // Hex format (32 hex chars)
        if (preg_match(self::HEX_PATTERN, $tenantId) && strlen($tenantId) === 32) {
            return true;
        }

        return false;
    }

    /**
     * Check if string is valid ID
     */
    private static function isValidId(string $id): bool
    {
        // UUID format
        if (preg_match(self::UUID_PATTERN, $id)) {
            return true;
        }

        // Hex format (32 hex chars)
        if (preg_match(self::HEX_PATTERN, $id) && strlen($id) === 32) {
            return true;
        }

        // Allow alphanumeric with hyphens/underscores (up to 255 chars)
        if (preg_match('/^[a-zA-Z0-9_-]{1,255}$/', $id)) {
            return true;
        }

        return false;
    }

    /**
     * Log validation failure for audit trail
     */
    private static function logValidationFailure(string $fieldName, string $reason): void
    {
        $entry = [
            'timestamp' => gmdate('c'),
            'field' => $fieldName,
            'reason' => $reason,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[1] ?? [],
        ];

        self::$validationLog[] = $entry;

        // Keep only last 1000 entries to prevent memory bloat
        if (count(self::$validationLog) > 1000) {
            array_shift(self::$validationLog);
        }

        // Log to error log if available
        if (function_exists('error_log')) {
            error_log("Validation failure - $fieldName: $reason");
        }
    }

    /**
     * Get validation log for audit purposes
     */
    public static function getValidationLog(): array
    {
        return self::$validationLog;
    }

    /**
     * Clear validation log
     */
    public static function clearValidationLog(): void
    {
        self::$validationLog = [];
    }
}
