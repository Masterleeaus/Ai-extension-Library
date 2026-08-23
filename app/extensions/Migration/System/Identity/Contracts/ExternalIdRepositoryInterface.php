<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Identity\Contracts;

use App\Extensions\Migration\System\Identity\ExternalIdScope;

interface ExternalIdRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function find(ExternalIdScope $scope): ?array;

    /** @return array<string, mixed> */
    public function store(
        ExternalIdScope $scope,
        string $targetType,
        string $targetId,
        string $checksum,
        ?int $entityPlanId = null,
    ): array;
}
