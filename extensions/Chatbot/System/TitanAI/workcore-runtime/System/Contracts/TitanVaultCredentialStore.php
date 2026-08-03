<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore\Contracts;

interface TitanVaultCredentialStore
{
    public function resolveForCompany(string $reference, int $companyId): ?string;
}
