<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\AI\Security;

use App\Domains\WorkCore\System\AI\Contracts\CredentialResolverContract;
use App\Domains\WorkCore\System\AI\Exceptions\AiConfigurationException;

final class EnvironmentCredentialResolver implements CredentialResolverContract
{
    public function resolve(string $reference, int $companyId): ?string
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        if (str_starts_with($reference, 'env:')) {
            $name = substr($reference, 4);
            if (! preg_match('/^[A-Z][A-Z0-9_]{2,127}$/', $name)) {
                throw new AiConfigurationException('Invalid AI environment credential reference.');
            }
            $value = env($name);
            return is_string($value) && $value !== '' ? $value : null;
        }

        if (str_starts_with($reference, 'config:')) {
            $path = substr($reference, 7);
            if (! preg_match('/^[a-zA-Z0-9_.-]{3,255}$/', $path)) {
                throw new AiConfigurationException('Invalid AI configuration credential reference.');
            }
            $value = config($path);
            return is_string($value) && $value !== '' ? $value : null;
        }

        throw new AiConfigurationException('Inline AI credentials are not allowed. Use env: or config: references, or bind a TitanVault credential resolver.');
    }
}
