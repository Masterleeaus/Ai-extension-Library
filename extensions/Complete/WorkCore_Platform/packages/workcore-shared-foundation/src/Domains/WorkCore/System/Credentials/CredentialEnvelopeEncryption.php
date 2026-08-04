<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Credentials;

use Illuminate\Encryption\Encrypter;
use InvalidArgumentException;

final class CredentialEnvelopeEncryption
{
    public function __construct(private Encrypter $encrypter) {}

    public function encryptCredential(mixed $credential): string
    {
        $json = json_encode($credential);
        if ($json === false) {
            throw new InvalidArgumentException('Credential must be JSON serializable');
        }

        return $this->encrypter->encrypt($json);
    }

    public function decryptCredential(string $encrypted): mixed
    {
        try {
            $json = $this->encrypter->decrypt($encrypted);
            return json_decode($json, true);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('Failed to decrypt credential: ' . $e->getMessage());
        }
    }

    public function encryptCredentialWithReference(
        mixed $credential,
        CredentialVaultReferenceContract $reference,
    ): array {
        return [
            'encrypted' => $this->encryptCredential($credential),
            'reference' => $reference->toArray(),
            'encrypted_at' => now()->toIso8601String(),
        ];
    }

    public function decryptCredentialFromEnvelope(array $envelope): mixed
    {
        if (!isset($envelope['encrypted'])) {
            throw new InvalidArgumentException('Envelope missing encrypted data');
        }

        return $this->decryptCredential($envelope['encrypted']);
    }
}
