<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore\Security;

use App\Domains\WorkCore\System\AI\Contracts\CredentialResolverContract;
use App\Domains\WorkCore\System\AI\Security\EnvironmentCredentialResolver;
use App\Services\AI\Integration\WorkCore\Contracts\TitanVaultCredentialStore;
use Illuminate\Contracts\Container\Container;

final class TitanVaultCredentialResolver implements CredentialResolverContract
{
    public function __construct(private Container $container, private EnvironmentCredentialResolver $fallback) {}

    public function resolve(string $reference, int $companyId): ?string
    {
        if (str_starts_with($reference, 'vault:') && $this->container->bound(TitanVaultCredentialStore::class)) {
            return $this->container->make(TitanVaultCredentialStore::class)->resolveForCompany($reference, $companyId);
        }
        return $this->fallback->resolve($reference, $companyId);
    }
}
