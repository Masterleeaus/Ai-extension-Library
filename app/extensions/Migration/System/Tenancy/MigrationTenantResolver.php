<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Tenancy;

final class MigrationTenantResolver
{
    public function __construct(private readonly MigrationTenantContext $context) {}

    public function companyId(): ?int
    {
        if ($explicit = $this->context->companyId()) {
            return $explicit;
        }

        $user = auth()->user();
        $activeCompanyId = $user?->getAttribute('active_company_id');

        if (is_numeric($activeCompanyId) && (int) $activeCompanyId > 0) {
            return (int) $activeCompanyId;
        }

        $configured = config('migration.default_company_id');

        return is_numeric($configured) && (int) $configured > 0
            ? (int) $configured
            : null;
    }
}
