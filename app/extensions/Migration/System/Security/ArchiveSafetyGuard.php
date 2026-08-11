<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Security;

use InvalidArgumentException;

final class ArchiveSafetyGuard
{
    private int $entries = 0;
    private int $expandedBytes = 0;

    public function __construct(
        private readonly int $maxEntries = 500,
        private readonly int $maxEntryBytes = 268435456,
        private readonly int $maxExpandedBytes = 1073741824,
        private readonly float $maxCompressionRatio = 200.0,
    ) {
    }

    public function reset(): void
    {
        $this->entries = 0;
        $this->expandedBytes = 0;
    }

    public function assertEntrySafe(string $name, int $uncompressedBytes, int $compressedBytes): void
    {
        if ($name === '' || str_contains($name, "\0")) {
            throw new InvalidArgumentException('Archive entry name is invalid.');
        }

        $normalised = str_replace('\\', '/', $name);
        if (str_starts_with($normalised, '/') || preg_match('/^[A-Za-z]:\//', $normalised) === 1) {
            throw new InvalidArgumentException('Absolute archive paths are not allowed.');
        }

        $segments = array_values(array_filter(explode('/', $normalised), static fn (string $segment): bool => $segment !== '' && $segment !== '.'));
        if (in_array('..', $segments, true)) {
            throw new InvalidArgumentException('Archive path traversal is not allowed.');
        }

        if ($uncompressedBytes < 0 || $compressedBytes < 0) {
            throw new InvalidArgumentException('Archive entry size metadata is invalid.');
        }

        if ($uncompressedBytes > $this->maxEntryBytes) {
            throw new InvalidArgumentException('Archive entry exceeds the configured expanded-size limit.');
        }

        $ratio = $uncompressedBytes / max(1, $compressedBytes);
        if ($uncompressedBytes > 0 && $ratio > $this->maxCompressionRatio) {
            throw new InvalidArgumentException('Archive entry has a suspicious compression ratio.');
        }

        $this->entries++;
        $this->expandedBytes += $uncompressedBytes;

        if ($this->entries > $this->maxEntries) {
            throw new InvalidArgumentException('Archive contains too many entries.');
        }

        if ($this->expandedBytes > $this->maxExpandedBytes) {
            throw new InvalidArgumentException('Archive expanded size exceeds the configured safety limit.');
        }
    }
}
