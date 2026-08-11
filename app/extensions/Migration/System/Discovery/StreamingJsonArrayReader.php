<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Discovery;

use JsonException;
use RuntimeException;

final class StreamingJsonArrayReader
{
    /** @return iterable<int, array<string, mixed>> */
    public function rows(string $path, int $maxItemBytes = 8388608): iterable
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open JSON source.');
        }

        try {
            $first = $this->nextNonWhitespace($handle);
            if ($first !== '[') {
                throw new RuntimeException('Streaming JSON connector requires a top-level JSON array.');
            }

            $buffer = '';
            $depth = 0;
            $inString = false;
            $escaped = false;

            while (($char = fgetc($handle)) !== false) {
                if ($inString) {
                    $buffer .= $char;
                    if ($escaped) {
                        $escaped = false;
                    } elseif ($char === '\\') {
                        $escaped = true;
                    } elseif ($char === '"') {
                        $inString = false;
                    }
                    $this->assertItemSize($buffer, $maxItemBytes);
                    continue;
                }

                if ($char === '"') {
                    $inString = true;
                    $buffer .= $char;
                    continue;
                }

                if ($char === '{' || $char === '[') {
                    $depth++;
                    $buffer .= $char;
                    $this->assertItemSize($buffer, $maxItemBytes);
                    continue;
                }

                if (($char === '}' || $char === ']') && $depth > 0) {
                    $depth--;
                    $buffer .= $char;
                    $this->assertItemSize($buffer, $maxItemBytes);
                    continue;
                }

                if ($char === ']' && $depth === 0) {
                    if (trim($buffer) !== '') {
                        yield $this->decodeItem($buffer);
                    }
                    return;
                }

                if ($char === ',' && $depth === 0) {
                    if (trim($buffer) !== '') {
                        yield $this->decodeItem($buffer);
                    }
                    $buffer = '';
                    continue;
                }

                $buffer .= $char;
                $this->assertItemSize($buffer, $maxItemBytes);
            }

            throw new RuntimeException('JSON array ended unexpectedly.');
        } finally {
            fclose($handle);
        }
    }

    /** @param resource $handle */
    private function nextNonWhitespace($handle): ?string
    {
        while (($char = fgetc($handle)) !== false) {
            if (! ctype_space($char)) {
                return $char;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function decodeItem(string $json): array
    {
        try {
            $decoded = json_decode(trim($json), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid JSON array item: ' . $exception->getMessage(), 0, $exception);
        }

        if (is_array($decoded) && ! array_is_list($decoded)) {
            return $decoded;
        }

        return ['value' => $decoded];
    }

    private function assertItemSize(string $buffer, int $maxItemBytes): void
    {
        if (strlen($buffer) > max(1024, $maxItemBytes)) {
            throw new RuntimeException('JSON item exceeds the configured per-record memory bound.');
        }
    }
}
