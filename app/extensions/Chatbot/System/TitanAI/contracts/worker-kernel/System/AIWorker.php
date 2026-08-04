<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

interface AIWorker
{
    public static function id(): string;
    public static function name(): string;
    public static function tier(): int;
    public static function role(): string;
    /** @return list<string> */
    public static function capabilities(): array;
    /** @return list<string> */
    public static function permissions(): array;
    /** @return array<string, mixed> */
    public static function definition(): array;
}
