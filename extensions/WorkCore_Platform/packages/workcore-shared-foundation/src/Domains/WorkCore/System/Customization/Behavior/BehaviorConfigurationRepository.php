<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Behavior;

use Illuminate\Database\ConnectionInterface;

final class BehaviorConfigurationRepository
{
    private string $tableName = 'workcore_behavior_configurations';

    public function __construct(private ConnectionInterface $connection) {}

    public function save(BehaviorConfigurationContract $configuration): void
    {
        $data = [
            'tenant_id' => $configuration->tenantId(),
            'id' => $configuration->id(),
            'name' => $configuration->name(),
            'model_temperature' => $configuration->modelTemperature(),
            'top_p' => $configuration->topP(),
            'max_tokens' => $configuration->maxTokens(),
            'response_style' => $configuration->responseStyle(),
            'tone' => $configuration->tone(),
            'personality_traits' => json_encode($configuration->personalityTraits()),
            'guardrails' => json_encode($configuration->guardrails()),
            'content_filter' => json_encode($configuration->contentFilter()),
            'bias_detection' => $configuration->biasDetection(),
            'hallucination_prevention' => $configuration->hallucinationPrevention(),
            'retry_strategy' => json_encode($configuration->retryStrategy()),
            'fallback_behavior' => $configuration->fallbackBehavior(),
            'version' => $configuration->version(),
            'is_active' => $configuration->isActive(),
            'created_at' => $configuration->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $configuration->updatedAt()->format('Y-m-d H:i:s'),
        ];

        $this->connection->table($this->tableName)->updateOrInsert(
            ['tenant_id' => $configuration->tenantId(), 'id' => $configuration->id()],
            $data
        );
    }

    public function findById(int $tenantId, string $configurationId): ?BehaviorConfigurationContract
    {
        $record = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('id', $configurationId)
            ->first();

        return $record ? $this->hydrate($record) : null;
    }

    public function findByName(int $tenantId, string $name): ?BehaviorConfigurationContract
    {
        $record = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->where('is_active', true)
            ->latest('version')
            ->first();

        return $record ? $this->hydrate($record) : null;
    }

    public function findActive(int $tenantId): array
    {
        $records = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->latest('updated_at')
            ->get();

        return $records->map(fn ($record) => $this->hydrate($record))->all();
    }

    public function findDefault(int $tenantId): ?BehaviorConfigurationContract
    {
        $record = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('created_at')
            ->first();

        return $record ? $this->hydrate($record) : null;
    }

    public function delete(int $tenantId, string $configurationId): bool
    {
        return $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('id', $configurationId)
            ->delete() > 0;
    }

    public function listVersions(int $tenantId, string $configurationId): array
    {
        $records = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('id', $configurationId)
            ->orderBy('version', 'desc')
            ->get();

        return $records->map(fn ($record) => $this->hydrate($record))->all();
    }

    private function hydrate(object $record): BehaviorConfigurationContract
    {
        return BehaviorConfiguration::from([
            'tenantId' => $record->tenant_id,
            'id' => $record->id,
            'name' => $record->name,
            'modelTemperature' => $record->model_temperature,
            'topP' => $record->top_p,
            'maxTokens' => $record->max_tokens,
            'responseStyle' => $record->response_style,
            'tone' => $record->tone,
            'personalityTraits' => json_decode($record->personality_traits, true) ?? [],
            'guardrails' => json_decode($record->guardrails, true) ?? [],
            'contentFilter' => json_decode($record->content_filter, true) ?? [],
            'biasDetection' => (bool) $record->bias_detection,
            'hallucinationPrevention' => (bool) $record->hallucination_prevention,
            'retryStrategy' => json_decode($record->retry_strategy, true) ?? [],
            'fallbackBehavior' => $record->fallback_behavior,
            'version' => $record->version,
            'isActive' => (bool) $record->is_active,
            'createdAt' => $record->created_at,
            'updatedAt' => $record->updated_at,
        ]);
    }

    public function setTableName(string $tableName): self
    {
        $this->tableName = $tableName;
        return $this;
    }
}
