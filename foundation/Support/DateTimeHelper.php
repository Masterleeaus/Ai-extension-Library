<?php

declare(strict_types=1);

namespace Foundation\Support;

use DateTimeImmutable;
use DateTimeZone;

class DateTimeHelper
{
    private static ?DateTimeZone $utcZone = null;

    public static function now(): string
    {
        return self::getUtcNow()->format('Y-m-d\TH:i:s.uP');
    }

    public static function nowAtom(): string
    {
        return self::getUtcNow()->format(DateTimeImmutable::ATOM);
    }

    public static function getUtcNow(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::getUtcZone());
    }

    public static function parse(string $timestamp): DateTimeImmutable
    {
        $formats = [
            DateTimeImmutable::ATOM,           // 2026-08-05T05:32:00+00:00
            'Y-m-d\TH:i:s.uP',                  // 2026-08-05T05:32:00.000000+00:00
            'Y-m-d H:i:s',                      // 2026-08-05 05:32:00
            'Y-m-d\TH:i:s',                     // 2026-08-05T05:32:00
            'Y-m-d',                            // 2026-08-05
        ];

        foreach ($formats as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $timestamp, self::getUtcZone());
            if ($parsed !== false) {
                return $parsed->setTimezone(self::getUtcZone());
            }
        }

        throw new \InvalidArgumentException("Unable to parse timestamp: {$timestamp}");
    }

    public static function toUnix(string $timestamp): int
    {
        return (int) self::parse($timestamp)->format('U');
    }

    public static function fromUnix(int $timestamp): string
    {
        return DateTimeImmutable::createFromFormat('U', (string) $timestamp, self::getUtcZone())
            ->format('Y-m-d\TH:i:s.uP');
    }

    public static function format(string $timestamp, string $format): string
    {
        return self::parse($timestamp)->format($format);
    }

    public static function isUtc(string $timestamp): bool
    {
        try {
            $dt = self::parse($timestamp);
            return $dt->getTimezone()->getName() === 'UTC';
        } catch (\Exception $e) {
            return false;
        }
    }

    private static function getUtcZone(): DateTimeZone
    {
        if (self::$utcZone === null) {
            self::$utcZone = new DateTimeZone('UTC');
        }
        return self::$utcZone;
    }
}
