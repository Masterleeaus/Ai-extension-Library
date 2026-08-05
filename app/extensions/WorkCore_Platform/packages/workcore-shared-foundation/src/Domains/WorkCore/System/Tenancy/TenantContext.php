<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Tenancy;

use App\Domains\WorkCore\System\Context\TenantContextSnapshot;
use App\Domains\WorkCore\System\Contracts\TenantContextContract;
use RuntimeException;

final class TenantContext implements TenantContextContract
{
    // Atomic state holder to prevent race conditions in concurrent contexts
    private ?TenantContextSnapshot $currentSnapshot = null;

    public function __construct(private ?int $companyId = null, private ?int $userId = null)
    {
        if ($companyId !== null || $userId !== null) {
            $this->currentSnapshot = new TenantContextSnapshot($companyId, $userId);
        }
    }

    public function hasTenant(): bool
    {
        return $this->currentSnapshot !== null && $this->currentSnapshot->companyId !== null;
    }

    public function companyId(): int
    {
        if (!$this->hasTenant()) {
            throw new RuntimeException('WorkCore tenant context has not been resolved.');
        }
        return $this->currentSnapshot->companyId;
    }

    public function userId(): ?int
    {
        return $this->currentSnapshot?->userId;
    }

    public function set(int $companyId, ?int $userId = null): void
    {
        $this->restore(new TenantContextSnapshot($companyId, $userId));
    }

    /**
     * Atomically restore tenant context from snapshot.
     * This ensures companyId and userId are always in consistent state.
     */
    public function restore(TenantContextSnapshot $snapshot): void
    {
        // ATOMIC: Assign snapshot as single operation to prevent race conditions
        // between multiple requests accessing companyId and userId
        $this->currentSnapshot = $snapshot;
        // Sync legacy properties for backwards compatibility
        $this->companyId = $snapshot->companyId;
        $this->userId = $snapshot->userId;
    }

    public function snapshot(): TenantContextSnapshot
    {
        if ($this->currentSnapshot === null) {
            return new TenantContextSnapshot(null, null);
        }
        return $this->currentSnapshot;
    }

    public function clear(): void
    {
        $this->currentSnapshot = null;
        $this->companyId = null;
        $this->userId = null;
    }
}
