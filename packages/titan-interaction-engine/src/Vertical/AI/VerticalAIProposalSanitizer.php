<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Vertical\AI;

use InvalidArgumentException;

final class VerticalAIProposalSanitizer
{
    private const MAX_STRING_LENGTH = 2000;
    private const MAX_KEY_LENGTH = 128;

    public function sanitize(array $payload): array
    {
        return $this->sanitizeValue($payload, '$');
    }

    private function sanitizeValue(mixed $value, string $path): mixed
    {
        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                if (is_string($key)) {
                    if (mb_strlen($key) > self::MAX_KEY_LENGTH) {
                        throw new InvalidArgumentException("Proposal key exceeds the length limit at {$path}.");
                    }
                    $this->assertSafeString($key, "{$path}.<key>");
                }
                $sanitized[$key] = $this->sanitizeValue($item, $path . '.' . (string) $key);
            }
            return $sanitized;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if (mb_strlen($trimmed) > self::MAX_STRING_LENGTH) {
                throw new InvalidArgumentException("Proposal string exceeds the length limit at {$path}.");
            }
            $this->assertSafeString($trimmed, $path);
            return $trimmed;
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
            return $value;
        }

        throw new InvalidArgumentException("Unsupported proposal value type at {$path}.");
    }

    private function assertSafeString(string $value, string $path): void
    {
        if (str_contains($value, "\0")) {
            throw new InvalidArgumentException("Null bytes are not allowed in proposal content at {$path}.");
        }

        if (preg_match('/<\s*\/?\s*[a-z!][^>]*>/i', $value) === 1) {
            throw new InvalidArgumentException("Executable markup is not allowed in proposal content at {$path}.");
        }

        if (preg_match('/(?:<\?php|```|javascript\s*:|data\s*:\s*text\/html|on[a-z]+\s*=|\beval\s*\(|\bfunction\s*\(|\bclass\s+[A-Za-z_])/i', $value) === 1) {
            throw new InvalidArgumentException("Executable code is not allowed in proposal content at {$path}.");
        }

        if (preg_match_all('~\b[a-z][a-z0-9+.-]*://[^\s<>"\']+~i', $value, $matches) > 0) {
            foreach ($matches[0] as $url) {
                if (!str_starts_with(strtolower($url), 'https://')) {
                    throw new InvalidArgumentException("Only HTTPS URLs are allowed in proposal content at {$path}.");
                }
            }
        }
    }
}
