<?php

declare(strict_types=1);

namespace App\Extensions\AIAgentWhatsappChannel\System\Domain\Services;

final class MediaValidationService
{
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    private const DISALLOWED_EXTENSIONS = ['exe', 'bat', 'sh', 'cmd', 'ps1', 'scr', 'vbs', 'jar', 'zip', 'rar'];

    public function isAllowedMimeType(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_TYPES);
    }

    public function getExtensionFromMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
    }

    public function validateFilename(string $filename): bool
    {
        $pathInfo = pathinfo($filename);
        $extension = strtolower($pathInfo['extension'] ?? '');

        // Reject suspicious extensions
        if (in_array($extension, self::DISALLOWED_EXTENSIONS)) {
            return false;
        }

        // Reject if filename contains path traversal attempts
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            return false;
        }

        return true;
    }

    public function sanitizeFilename(string $filename): string
    {
        // Keep only alphanumeric, dots, hyphens, underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);

        // Ensure minimum length
        if (strlen($sanitized) === 0) {
            $sanitized = 'attachment';
        }

        return $sanitized;
    }
}
