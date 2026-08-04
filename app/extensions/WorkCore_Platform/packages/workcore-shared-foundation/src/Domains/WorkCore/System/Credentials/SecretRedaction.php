<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Credentials;

final class SecretRedaction
{
    private static array $secretPatterns = [
        'password' => '/["\']?password["\']?\s*[:=]\s*["\']?([^\s"\']+)["\']?/i',
        'token' => '/["\']?token["\']?\s*[:=]\s*["\']?([^\s"\']+)["\']?/i',
        'api_key' => '/["\']?api[_-]?key["\']?\s*[:=]\s*["\']?([^\s"\']+)["\']?/i',
        'secret' => '/["\']?secret["\']?\s*[:=]\s*["\']?([^\s"\']+)["\']?/i',
        'authorization' => '/["\']?authorization["\']?\s*[:=]\s*Bearer\s+([^\s"\']+)/i',
        'credentials' => '/["\']?credentials["\']?\s*[:=]\s*["\']?([^\s"\']+)["\']?/i',
    ];

    private static string $redactionMask = '[REDACTED]';

    public static function redact(mixed $data, bool $recursive = true): mixed
    {
        if (is_string($data)) {
            return self::redactString($data);
        }

        if (is_array($data) && $recursive) {
            return self::redactArray($data);
        }

        if (is_object($data) && $recursive) {
            return self::redactObject($data);
        }

        return $data;
    }

    private static function redactString(string $data): string
    {
        foreach (self::$secretPatterns as $pattern) {
            $data = preg_replace_callback(
                $pattern,
                fn ($matches) => str_replace($matches[1], self::$redactionMask, $matches[0]),
                $data
            );
        }

        return $data;
    }

    private static function redactArray(array $data): array
    {
        $secretKeys = self::getSecretKeys();
        $redacted = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $secretKeys)) {
                $redacted[$key] = self::$redactionMask;
            } elseif (is_array($value) || is_object($value)) {
                $redacted[$key] = self::redact($value, true);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }

    private static function redactObject(object $data): object
    {
        $secretKeys = self::getSecretKeys();
        $reflection = new \ReflectionClass($data);

        foreach ($reflection->getProperties() as $property) {
            if (in_array(strtolower($property->getName()), $secretKeys)) {
                $property->setAccessible(true);
                $property->setValue($data, self::$redactionMask);
            }
        }

        return $data;
    }

    private static function getSecretKeys(): array
    {
        return [
            'password',
            'passwd',
            'pwd',
            'token',
            'access_token',
            'refresh_token',
            'api_key',
            'apikey',
            'secret',
            'private_key',
            'privatekey',
            'authorization',
            'credentials',
            'client_secret',
            'oauth_token',
        ];
    }

    public static function setRedactionMask(string $mask): void
    {
        self::$redactionMask = $mask;
    }

    public static function addPattern(string $name, string $pattern): void
    {
        self::$secretPatterns[$name] = $pattern;
    }
}
