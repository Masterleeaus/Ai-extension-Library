<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\CredentialVaultContract;

class CredentialVault implements CredentialVaultContract
{
    private array $credentials = [];
    private const REDACTION_PLACEHOLDER = '***REDACTED***';

    public function store(
        string $tenantId,
        string $key,
        string $secret,
        ?string $type = null,
        array $metadata = []
    ): string {
        $storageKey = "{$tenantId}:{$key}";
        $credentialId = hash('sha256', $storageKey . time());

        $this->credentials[$storageKey] = [
            'id' => $credentialId,
            'secret' => $this->encrypt($secret),
            'type' => $type,
            'metadata' => $metadata,
            'created_at' => time(),
            'last_accessed_at' => null,
        ];

        return $credentialId;
    }

    public function retrieve(string $tenantId, string $key): ?string
    {
        $storageKey = "{$tenantId}:{$key}";

        if (!isset($this->credentials[$storageKey])) {
            return null;
        }

        $credential = &$this->credentials[$storageKey];
        $credential['last_accessed_at'] = time();

        return $this->decrypt($credential['secret']);
    }

    public function delete(string $tenantId, string $key): bool
    {
        $storageKey = "{$tenantId}:{$key}";

        if (!isset($this->credentials[$storageKey])) {
            return false;
        }

        unset($this->credentials[$storageKey]);
        return true;
    }

    public function exists(string $tenantId, string $key): bool
    {
        $storageKey = "{$tenantId}:{$key}";
        return isset($this->credentials[$storageKey]);
    }

    public function rotate(string $tenantId, string $key, string $newSecret): bool
    {
        if (!$this->exists($tenantId, $key)) {
            return false;
        }

        $storageKey = "{$tenantId}:{$key}";
        $this->credentials[$storageKey]['secret'] = $this->encrypt($newSecret);
        $this->credentials[$storageKey]['rotated_at'] = time();

        return true;
    }

    public function list(string $tenantId, ?string $type = null): array
    {
        $result = [];

        foreach ($this->credentials as $storageKey => $credential) {
            if (strpos($storageKey, "{$tenantId}:") !== 0) {
                continue;
            }

            if ($type && $credential['type'] !== $type) {
                continue;
            }

            $result[] = [
                'key' => substr($storageKey, strlen($tenantId) + 1),
                'type' => $credential['type'],
                'metadata' => $credential['metadata'],
                'created_at' => $credential['created_at'],
            ];
        }

        return $result;
    }

    public function redact(string $value): string
    {
        if (empty($value)) {
            return $value;
        }

        if (strlen($value) <= 4) {
            return self::REDACTION_PLACEHOLDER;
        }

        $firstChar = substr($value, 0, 1);
        $lastChar = substr($value, -1);

        return $firstChar . self::REDACTION_PLACEHOLDER . $lastChar;
    }

    public function isRedacted(string $value): bool
    {
        return strpos($value, self::REDACTION_PLACEHOLDER) !== false;
    }

    private function encrypt(string $value): string
    {
        return base64_encode($value);
    }

    private function decrypt(string $value): string
    {
        return base64_decode($value, true) ?: $value;
    }
}
