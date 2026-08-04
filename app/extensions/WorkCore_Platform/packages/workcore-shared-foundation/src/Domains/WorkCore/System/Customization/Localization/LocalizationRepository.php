<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Localization;

use Illuminate\Database\ConnectionInterface;

final class LocalizationRepository
{
    private string $tableName = 'workcore_localizations';

    public function __construct(private ConnectionInterface $connection) {}

    public function save(LocalizationContract $localization): void
    {
        $data = [
            'tenant_id' => $localization->tenantId(),
            'id' => $localization->id(),
            'language' => $localization->language(),
            'region' => $localization->region(),
            'translations' => json_encode($localization->toArray()['translations']),
            'regional_settings' => json_encode($localization->getRegionalSettings()),
            'version' => $localization->version(),
            'is_active' => $localization->isActive(),
            'created_at' => $localization->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $localization->updatedAt()->format('Y-m-d H:i:s'),
        ];

        $this->connection->table($this->tableName)->updateOrInsert(
            ['tenant_id' => $localization->tenantId(), 'id' => $localization->id()],
            $data
        );
    }

    public function findById(int $tenantId, string $localizationId): ?LocalizationContract
    {
        $record = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('id', $localizationId)
            ->first();

        return $record ? $this->hydrate($record) : null;
    }

    public function findByLanguage(int $tenantId, string $language): ?LocalizationContract
    {
        $record = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('language', $language)
            ->where('is_active', true)
            ->latest('version')
            ->first();

        return $record ? $this->hydrate($record) : null;
    }

    public function findByLanguageAndRegion(int $tenantId, string $language, string $region): ?LocalizationContract
    {
        $record = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('language', $language)
            ->where('region', $region)
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

    public function findAllLanguages(int $tenantId): array
    {
        $records = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->distinct('language')
            ->get();

        return $records->pluck('language')->all();
    }

    public function delete(int $tenantId, string $localizationId): bool
    {
        return $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('id', $localizationId)
            ->delete() > 0;
    }

    public function listVersions(int $tenantId, string $localizationId): array
    {
        $records = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('id', $localizationId)
            ->orderBy('version', 'desc')
            ->get();

        return $records->map(fn ($record) => $this->hydrate($record))->all();
    }

    private function hydrate(object $record): LocalizationContract
    {
        return Localization::from([
            'tenantId' => $record->tenant_id,
            'id' => $record->id,
            'language' => $record->language,
            'region' => $record->region,
            'translations' => json_decode($record->translations, true) ?? [],
            'regionalSettings' => json_decode($record->regional_settings, true) ?? [],
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
