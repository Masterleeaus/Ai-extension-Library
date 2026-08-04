<?php

declare(strict_types=1);

namespace App\Extensions\AIAgentWhatsappChannel\System\Media\Exceptions;

use Exception;

class MediaQuarantineException extends Exception
{
    public static function oversized(int $byteSize, int $maxBytes): self
    {
        return new self(
            "Media size {$byteSize} bytes exceeds maximum {$maxBytes} bytes",
        );
    }

    public static function unsupportedMimeType(string $mimeType, array $allowed): self
    {
        return new self(
            "MIME type '{$mimeType}' not in allowed list: " . implode(', ', $allowed),
        );
    }

    public static function magicByteMismatch(string $detected, string $claimed): self
    {
        return new self(
            "Detected MIME type '{$detected}' does not match claimed '{$claimed}'",
        );
    }

    public static function webhookVerificationFailed(): self
    {
        return new self('Webhook signature verification failed');
    }

    public static function malwareSuspected(string $scanResult): self
    {
        return new self("Malware scan detected threat: {$scanResult}");
    }
}
