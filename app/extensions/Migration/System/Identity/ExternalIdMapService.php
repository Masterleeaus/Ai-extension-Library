<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Identity;

use App\Extensions\Migration\System\Identity\Contracts\ExternalIdRepositoryInterface;

final class ExternalIdMapService
{
    public function __construct(
        private readonly ExternalIdRepositoryInterface $repository,
        private readonly CanonicalChecksum $checksum,
    ) {
    }

    /** @return array<string, mixed>|null */
    public function find(ExternalIdScope $scope): ?array
    {
        return $this->repository->find($scope);
    }

    /** @param array<string, mixed> $canonicalPayload
     *  @return array<string, mixed>
     */
    public function record(
        ExternalIdScope $scope,
        string $targetType,
        string $targetId,
        array $canonicalPayload,
        ?int $entityPlanId = null,
    ): array {
        return $this->repository->store(
            $scope,
            $targetType,
            $targetId,
            $this->checksum->hash($canonicalPayload),
            $entityPlanId,
        );
    }

    /** @param array<string, mixed> $canonicalPayload */
    public function isUnchanged(ExternalIdScope $scope, array $canonicalPayload): bool
    {
        $existing = $this->repository->find($scope);
        if ($existing === null) {
            return false;
        }

        return hash_equals(
            (string) ($existing['checksum'] ?? ''),
            $this->checksum->hash($canonicalPayload),
        );
    }
}
