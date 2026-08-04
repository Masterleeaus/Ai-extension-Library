<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Services;

use App\Domains\Entity\Enums\EntityEnum;

interface AIPhotoshootProviderInterface
{
    /**
     * Kick off a generation and return a request identifier (UUID) that
     * `check()` can later be polled with.
     *
     * @param  array<int, string>  $imageUrls
     * @param  string  $aspectRatio  e.g. '21:9', '1:1', or 'auto' to let the provider decide
     * @param  string  $resolution  e.g. '1K', '2K', '4K'
     */
    public function generate(
        string $prompt,
        array $imageUrls,
        int $numImages,
        string $aspectRatio,
        string $resolution
    ): string;

    /**
     * Check the status of a previously-started generation.
     *
     * @return array{images: array<int, string>, size: string}|null
     *                                                              Returns null while the job is still in progress
     */
    public function check(string $uuid): ?array;

    /**
     * The EntityEnum this provider maps to. Used by the base controller for
     * credit calculation via `Entity::driver(...)`.
     */
    public function entityEnum(): EntityEnum;
}
