<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Packages;

use InvalidArgumentException;

final readonly class SkillResourceFingerprint
{
    public function __construct(
        public string $path,
        public string $sha256,
        public int $size,
        public ?string $mimeType = null,
    ) {
        if ($this->path === '' || str_starts_with($this->path, '/') || in_array('..', explode('/', $this->path), true)) {
            throw new InvalidArgumentException('Skill resource paths must be safe relative paths.');
        }

        if (! preg_match('/^[a-f0-9]{64}$/', $this->sha256)) {
            throw new InvalidArgumentException('Skill resource SHA-256 must contain 64 lowercase hexadecimal characters.');
        }

        if ($this->size < 0) {
            throw new InvalidArgumentException('Skill resource size cannot be negative.');
        }
    }

    public static function fromContent(string $path, string $content, ?string $mimeType = null): self
    {
        return new self(
            path: self::normalisePath($path),
            sha256: hash('sha256', $content),
            size: strlen($content),
            mimeType: $mimeType,
        );
    }

    public static function fromHash(string $path, string $sha256, int $size, ?string $mimeType = null): self
    {
        return new self(self::normalisePath($path), strtolower($sha256), $size, $mimeType);
    }

    /** @return array{path: string, sha256: string, size: int, mime_type: string|null} */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'sha256' => $this->sha256,
            'size' => $this->size,
            'mime_type' => $this->mimeType,
        ];
    }

    public static function normalisePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..' || str_contains($segment, "\0")) {
                throw new InvalidArgumentException('Skill resource path traversal is not allowed.');
            }
            $segments[] = $segment;
        }

        $normalised = implode('/', $segments);
        if ($normalised === '') {
            throw new InvalidArgumentException('Skill resource path cannot be empty.');
        }

        return $normalised;
    }
}
