<?php

declare(strict_types=1);

namespace Foundation\Support;

use RuntimeException;

/**
 * Safe JSON decoding helper with proper error handling
 *
 * This class provides a safe alternative to json_decode() that:
 * - Checks for JSON parsing errors
 * - Throws meaningful exceptions on failure
 * - Ensures type safety
 * - Provides consistent error logging
 */
class JsonHelper
{
    /**
     * Safely decode a JSON string
     *
     * @param string $json The JSON string to decode
     * @param bool $associative Return associative array (default: true)
     * @param int $depth Maximum nesting depth (default: 512)
     * @param int $flags JSON decode flags (default: 0)
     *
     * @return mixed The decoded JSON value
     *
     * @throws RuntimeException If JSON decoding fails
     */
    public static function decode(
        string $json,
        bool $associative = true,
        int $depth = 512,
        int $flags = 0
    ): mixed {
        // Handle empty or whitespace-only strings
        if (empty(trim($json))) {
            throw new RuntimeException('Cannot decode empty JSON string');
        }

        // Use JSON_THROW_ON_ERROR for cleaner error handling (PHP 7.3+)
        try {
            $decoded = json_decode(
                $json,
                $associative,
                $depth,
                $flags | JSON_THROW_ON_ERROR
            );
            return $decoded;
        } catch (\JsonException $e) {
            $errorMsg = sprintf(
                'Failed to decode JSON: %s (Error: %s)',
                $e->getMessage(),
                json_last_error_msg()
            );

            // Log the error for debugging
            error_log($errorMsg);

            throw new RuntimeException($errorMsg, 0, $e);
        }
    }

    /**
     * Safely decode JSON with a fallback value on error
     *
     * This method is useful when you want to provide a default value
     * instead of throwing an exception.
     *
     * @param string $json The JSON string to decode
     * @param mixed $default The default value to return on error
     * @param bool $associative Return associative array (default: true)
     * @param int $depth Maximum nesting depth (default: 512)
     * @param int $flags JSON decode flags (default: 0)
     *
     * @return mixed The decoded JSON value or default value on error
     */
    public static function decodeWithDefault(
        string $json,
        mixed $default = null,
        bool $associative = true,
        int $depth = 512,
        int $flags = 0
    ): mixed {
        try {
            return self::decode($json, $associative, $depth, $flags);
        } catch (RuntimeException $e) {
            error_log('JSON decoding failed, using default value: ' . $e->getMessage());
            return $default;
        }
    }

    /**
     * Validate JSON string without decoding
     *
     * @param string $json The JSON string to validate
     *
     * @return bool True if JSON is valid, false otherwise
     */
    public static function isValid(string $json): bool {
        if (empty(trim($json))) {
            return false;
        }

        json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return true;
    }
}
