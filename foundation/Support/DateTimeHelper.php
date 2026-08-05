<?php

declare(strict_types=1);

namespace Foundation\Support;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Standardized DateTime handling for UTC timestamps and timezone consistency.
 *
 * This helper ensures:
 * - All timestamps stored in UTC
 * - ISO 8601 format for database storage
 * - Microsecond precision for high-resolution timestamps
 * - Consistent timezone handling across all systems
 */
class DateTimeHelper
{
    /**
     * Get current UTC timestamp in ISO 8601 format.
     *
     * @return string ISO 8601 formatted UTC timestamp (e.g., "2026-08-05T10:30:45+00:00")
     */
    public static function now(): string
    {
        return self::currentDateTime()->format('c');
    }

    /**
     * Get current UTC timestamp with microseconds (ISO 8601 extended format).
     *
     * @return string ISO 8601 formatted UTC timestamp with microseconds
     */
    public static function nowWithMicroseconds(): string
    {
        return self::currentDateTime()->format('Y-m-d\TH:i:s.uP');
    }

    /**
     * Get current DateTimeImmutable object in UTC.
     *
     * @return DateTimeImmutable Current UTC time
     */
    public static function currentDateTime(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    /**
     * Parse a timestamp string and ensure it's in UTC.
     *
     * @param string $timestamp Timestamp string (ISO 8601 or other format)
     * @param DateTimeZone|null $timezone Source timezone if not UTC
     * @return DateTimeImmutable Parsed datetime in UTC
     */
    public static function parse(string $timestamp, ?DateTimeZone $timezone = null): DateTimeImmutable
    {
        $timezone = $timezone ?? new DateTimeZone('UTC');
        $dt = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s.u|Y-m-d\TH:i:s.uP|Y-m-d\TH:i:sP|Y-m-d H:i:s',
            $timestamp,
            $timezone
        );

        if ($dt === false) {
            throw new \InvalidArgumentException("Unable to parse timestamp: {$timestamp}");
        }

        return $dt->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * Format a datetime for storage (ISO 8601 UTC).
     *
     * @param DateTimeImmutable $dateTime DateTime object to format
     * @return string ISO 8601 formatted string in UTC
     */
    public static function formatForStorage(DateTimeImmutable $dateTime): string
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'))->format('c');
    }

    /**
     * Format a datetime for display with microseconds.
     *
     * @param DateTimeImmutable $dateTime DateTime object to format
     * @return string ISO 8601 formatted string with microseconds in UTC
     */
    public static function formatWithMicroseconds(DateTimeImmutable $dateTime): string
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.uP');
    }

    /**
     * Convert a UTC timestamp to a specific timezone for display.
     *
     * @param string $utcTimestamp ISO 8601 UTC timestamp
     * @param string $timezone Target timezone (e.g., 'America/New_York')
     * @param string $format Output format (default: ISO 8601)
     * @return string Formatted timestamp in target timezone
     */
    public static function toTimezone(
        string $utcTimestamp,
        string $timezone,
        string $format = 'c'
    ): string {
        $dt = self::parse($utcTimestamp);
        return $dt->setTimezone(new DateTimeZone($timezone))->format($format);
    }

    /**
     * Get Unix timestamp (seconds since epoch) from UTC ISO 8601 string.
     *
     * @param string $utcTimestamp ISO 8601 UTC timestamp
     * @return int Unix timestamp
     */
    public static function toUnixTimestamp(string $utcTimestamp): int
    {
        return (int) self::parse($utcTimestamp)->format('U');
    }

    /**
     * Create timestamp from Unix timestamp.
     *
     * @param int $unixTimestamp Unix timestamp (seconds since epoch)
     * @return string ISO 8601 UTC timestamp
     */
    public static function fromUnixTimestamp(int $unixTimestamp): string
    {
        $dt = DateTimeImmutable::createFromFormat('U', (string) $unixTimestamp, new DateTimeZone('UTC'));
        if ($dt === false) {
            throw new \InvalidArgumentException("Invalid Unix timestamp: {$unixTimestamp}");
        }
        return $dt->format('c');
    }

    /**
     * Validate that a timestamp is in ISO 8601 UTC format.
     *
     * @param string $timestamp Timestamp to validate
     * @return bool True if valid ISO 8601 UTC format
     */
    public static function isValidUtcTimestamp(string $timestamp): bool
    {
        try {
            self::parse($timestamp);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Get difference between two UTC timestamps in seconds.
     *
     * @param string $timestamp1 First ISO 8601 UTC timestamp
     * @param string $timestamp2 Second ISO 8601 UTC timestamp
     * @return int Difference in seconds (timestamp2 - timestamp1)
     */
    public static function differenceInSeconds(string $timestamp1, string $timestamp2): int
    {
        $dt1 = self::parse($timestamp1);
        $dt2 = self::parse($timestamp2);
        return (int) $dt2->format('U') - (int) $dt1->format('U');
    }

    /**
     * Add seconds to a UTC timestamp.
     *
     * @param string $utcTimestamp ISO 8601 UTC timestamp
     * @param int $seconds Number of seconds to add (can be negative)
     * @return string New ISO 8601 UTC timestamp
     */
    public static function addSeconds(string $utcTimestamp, int $seconds): string
    {
        $dt = self::parse($utcTimestamp);
        return $dt->modify("{$seconds} seconds")->format('c');
    }
}
