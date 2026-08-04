<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Branding;

use Illuminate\Database\ConnectionInterface;

final class ThemeRepository
{
    private string $tableName = 'workcore_themes';

    public function __construct(private ConnectionInterface $connection) {}

    public function save(ThemeContract $theme): void
    {
        $data = [
            'tenant_id' => $theme->tenantId(),
            'id' => $theme->id(),
            'name' => $theme->name(),
            'primary_color' => $theme->primaryColor(),
            'secondary_color' => $theme->secondaryColor(),
            'accent_color' => $theme->accentColor(),
            'font_family' => $theme->fontFamily(),
            'logo_url' => $theme->logoUrl(),
            'favicon_url' => $theme->faviconUrl(),
            'custom_css' => $theme->customCSS(),
            'is_dark_mode' => $theme->isDarkMode(),
            'version' => $theme->version(),
            'is_active' => $theme->isActive(),
            'created_at' => $theme->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $theme->updatedAt()->format('Y-m-d H:i:s'),
        ];

        $this->connection->table($this->tableName)->updateOrInsert(
            ['tenant_id' => $theme->tenantId(), 'id' => $theme->id()],
            $data
        );
    }

    public function findById(int $tenantId, string $themeId): ?ThemeContract
    {
        $record = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('id', $themeId)
            ->first();

        return $record ? $this->hydrate($record) : null;
    }

    public function findByName(int $tenantId, string $name): ?ThemeContract
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

    public function findDefault(int $tenantId): ?ThemeContract
    {
        $record = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('created_at')
            ->first();

        return $record ? $this->hydrate($record) : null;
    }

    public function delete(int $tenantId, string $themeId): bool
    {
        return $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('id', $themeId)
            ->delete() > 0;
    }

    public function listVersions(int $tenantId, string $themeId): array
    {
        $records = $this->connection->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('id', $themeId)
            ->orderBy('version', 'desc')
            ->get();

        return $records->map(fn ($record) => $this->hydrate($record))->all();
    }

    private function hydrate(object $record): ThemeContract
    {
        return Theme::from([
            'tenantId' => $record->tenant_id,
            'id' => $record->id,
            'name' => $record->name,
            'primaryColor' => $record->primary_color,
            'secondaryColor' => $record->secondary_color,
            'accentColor' => $record->accent_color,
            'fontFamily' => $record->font_family,
            'logoUrl' => $record->logo_url,
            'faviconUrl' => $record->favicon_url,
            'customCSS' => $record->custom_css,
            'isDarkMode' => (bool) $record->is_dark_mode,
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
