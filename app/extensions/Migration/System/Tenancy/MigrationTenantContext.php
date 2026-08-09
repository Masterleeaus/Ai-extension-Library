<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Tenancy;

use Closure;

final class MigrationTenantContext
{
    private ?int $companyId = null;

    public function companyId(): ?int
    {
        return $this->companyId;
    }

    public function set(int $companyId): void
    {
        if ($companyId <= 0) {
            throw new \InvalidArgumentException('Migration tenant company ID must be positive.');
        }

        $this->companyId = $companyId;
    }

    public function clear(): void
    {
        $this->companyId = null;
    }

    public function run(int $companyId, Closure $callback): mixed
    {
        $previous = $this->companyId;
        $this->set($companyId);

        try {
            return $callback();
        } finally {
            $this->companyId = $previous;
        }
    }
}
