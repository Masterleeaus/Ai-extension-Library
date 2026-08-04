<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Security;

final class WebhookSignatureVerifier
{
    /** @param array<string,string|string[]> $headers */
    public static function meta(array $headers, string $body, string $secret): bool
    {
        if ($secret === '') {
            return false;
        }

        $provided = self::header($headers, 'x-hub-signature-256');
        if ($provided === null || !str_starts_with($provided, 'sha256=')) {
            return false;
        }

        return hash_equals('sha256=' . hash_hmac('sha256', $body, $secret), $provided);
    }

    /** @param array<string,string|string[]> $headers */
    public static function telegram(array $headers, string $secret): bool
    {
        if ($secret === '') {
            return false;
        }

        $provided = self::header($headers, 'x-telegram-bot-api-secret-token');

        return $provided !== null && hash_equals($secret, $provided);
    }

    /** @param array<string,string|string[]> $headers */
    public static function hmac(array $headers, string $body, string $secret, string $header = 'x-chatbot-signature'): bool
    {
        if ($secret === '') {
            return false;
        }

        $provided = self::header($headers, $header);
        if ($provided === null) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $body, $secret), $provided);
    }

    /** @param array<string,string|string[]> $headers */
    private static function header(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strtolower($key) !== $name) {
                continue;
            }

            $value = is_array($value) ? reset($value) : $value;

            return is_string($value) && $value !== '' ? $value : null;
        }

        return null;
    }
}
