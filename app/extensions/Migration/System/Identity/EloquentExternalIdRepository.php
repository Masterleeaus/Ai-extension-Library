<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Identity;

use App\Extensions\Migration\System\Identity\Contracts\ExternalIdRepositoryInterface;
use App\Extensions\Migration\System\Models\MigrationExternalId;
use InvalidArgumentException;

final class EloquentExternalIdRepository implements ExternalIdRepositoryInterface
{
    public function find(ExternalIdScope $scope): ?array
    {
        $model = MigrationExternalId::query()
            ->where('company_id', $scope->companyId)
            ->where('project_id', $scope->projectId)
            ->where('connection_id', $scope->connectionId)
            ->where('source_type', $scope->sourceType)
            ->where('source_id', $scope->sourceId)
            ->first();

        return $model?->toArray();
    }

    public function store(
        ExternalIdScope $scope,
        string $targetType,
        string $targetId,
        string $checksum,
        ?int $entityPlanId = null,
    ): array {
        if (trim($targetType) === '' || trim($targetId) === '' || trim($checksum) === '') {
            throw new InvalidArgumentException('External ID target type, target ID and checksum are required.');
        }

        $model = MigrationExternalId::query()->updateOrCreate(
            [
                'company_id' => $scope->companyId,
                'project_id' => $scope->projectId,
                'connection_id' => $scope->connectionId,
                'source_type' => $scope->sourceType,
                'source_id' => $scope->sourceId,
            ],
            [
                'entity_plan_id' => $entityPlanId,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'checksum' => $checksum,
                'migrated_at' => now(),
            ],
        );

        return $model->toArray();
    }
}
