<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore\Contracts;

/**
 * Stable boundary for authoritative WorkCore read models supplied by the host.
 */
interface HostWorkCoreReadBus
{
    public function supports(string $readModel): bool;

    /** @param array<string,mixed> $input @return array<string,mixed>|list<mixed>|scalar|null */
    public function read(string $readModel, array $input, int $companyId, int $actorId): mixed;
}
