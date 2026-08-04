<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Prompts;

use Illuminate\Database\ConnectionInterface;

final class PromptRepository implements PromptRepositoryContract
{
    private string $tableName = 'workcore_prompt_templates';

    public function __construct(private ConnectionInterface $connection) {}

    public function save(PromptTemplateContract $prompt): void
    {
        $data = [
            'tenant_id' => $prompt->tenantId(),
            'id' => $prompt->id(),
            'name' => $prompt->name(),
            'description' => $prompt->description(),
            'category' => $prompt->category(),
            'template' => $prompt->template(),
            'variables' => json_encode($prompt->variables()),
            'version' => $prompt->version(),
            'is_active' => $prompt->isActive(),
            'metadata' => json_encode($prompt->metadata()),
            'created_at' => $prompt->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $prompt->updatedAt()->format('Y-m-d H:i:s'),
        ];

        $this->connection->table($this->tableName)->updateOrInsert(
            ['tenant_id' => $prompt->tenantId(), 'id' => $prompt->id()],
            $data
        );
    }

    public function findById(int $tenantId, string $promptId): ?PromptTemplateContract
    {
        $record = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('id', $promptId)
            ->first();

        return $record ? $this->hydrate($record) : null;
    }

    public function findByName(int $tenantId, string $name): ?PromptTemplateContract
    {
        $record = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->where('is_active', true)
            ->latest('version')
            ->first();

        return $record ? $this->hydrate($record) : null;
    }

    public function findByCategory(int $tenantId, string $category): array
    {
        $records = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('category', $category)
            ->where('is_active', true)
            ->latest('updated_at')
            ->get();

        return $records->map(fn ($record) => $this->hydrate($record))->all();
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

    public function delete(int $tenantId, string $promptId): bool
    {
        return $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('id', $promptId)
            ->delete() > 0;
    }

    public function listVersions(int $tenantId, string $promptId): array
    {
        $records = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('id', $promptId)
            ->orderBy('version', 'desc')
            ->get();

        return $records->map(fn ($record) => $this->hydrate($record))->all();
    }

    public function getVersion(int $tenantId, string $promptId, int $version): ?PromptTemplateContract
    {
        $record = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('id', $promptId)
            ->where('version', $version)
            ->first();

        return $record ? $this->hydrate($record) : null;
    }

    private function hydrate(object $record): PromptTemplateContract
    {
        return PromptTemplate::from([
            'tenantId' => $record->tenant_id,
            'id' => $record->id,
            'name' => $record->name,
            'description' => $record->description,
            'category' => $record->category,
            'template' => $record->template,
            'variables' => json_decode($record->variables, true) ?? [],
            'version' => $record->version,
            'isActive' => (bool) $record->is_active,
            'metadata' => json_decode($record->metadata, true) ?? [],
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
