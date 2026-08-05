<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\BrandingThemingContract;
use PDO;
use Foundation\Support\JsonHelper;
use Foundation\Support\DateTimeHelper;

class BrandingTheming implements BrandingThemingContract
{
    private PDO $db;
    private string $tablePrefix = 'branding_theming_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createTheme(
        string $tenantId,
        string $themeName,
        array $themeConfig
    ): string {
        $themeId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}themes (id, tenant_id, name, config, created_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $themeId,
            $tenantId,
            $themeName,
            json_encode($themeConfig),
            DateTimeHelper::now(),
        ]);

        return $themeId;
    }

    public function getTheme(
        string $tenantId,
        string $themeId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}themes WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$themeId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['config'] = JsonHelper::decode($result['config']);
        }

        return $result ?: null;
    }

    public function updateTheme(
        string $tenantId,
        string $themeId,
        array $themeConfig
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}themes SET config = ?, updated_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([json_encode($themeConfig), DateTimeHelper::now(), $themeId, $tenantId]);
    }

    public function applyTheme(
        string $tenantId,
        string $themeId
    ): bool {
        $theme = $this->getTheme($tenantId, $themeId);

        if (!$theme) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}tenant_active_theme SET theme_id = ?, applied_at = ? WHERE tenant_id = ?"
        );

        $result = $stmt->execute([$themeId, DateTimeHelper::now(), $tenantId]);

        if ($stmt->rowCount() === 0) {
            $insertStmt = $this->db->prepare(
                "INSERT INTO {$this->tablePrefix}tenant_active_theme (tenant_id, theme_id, applied_at)
                 VALUES (?, ?, ?)"
            );

            return $insertStmt->execute([$tenantId, $themeId, DateTimeHelper::now()]);
        }

        return $result;
    }

    public function getActiveTheme(
        string $tenantId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT t.* FROM {$this->tablePrefix}themes t
             INNER JOIN {$this->tablePrefix}tenant_active_theme tat ON t.id = tat.theme_id
             WHERE t.tenant_id = ? AND tat.tenant_id = ?"
        );

        $stmt->execute([$tenantId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['config'] = JsonHelper::decode($result['config']);
        }

        return $result ?: null;
    }

    public function listThemes(
        string $tenantId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT id, name, created_at FROM {$this->tablePrefix}themes WHERE tenant_id = ? ORDER BY created_at DESC"
        );

        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function previewTheme(
        string $tenantId,
        string $themeId
    ): string {
        $theme = $this->getTheme($tenantId, $themeId);

        if (!$theme) {
            return '';
        }

        return json_encode($theme['config'], JSON_PRETTY_PRINT);
    }

    public function deleteTheme(
        string $tenantId,
        string $themeId
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->tablePrefix}themes WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([$themeId, $tenantId]);
    }
}
