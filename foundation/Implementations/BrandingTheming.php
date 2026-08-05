<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\BrandingThemingContract;
use PDO;
use Foundation\Support\JsonHelper;

class BrandingTheming implements BrandingThemingContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'branding_theming_';
    private const TABLE_TENANT_ACTIVE_THEME = self::TABLE_PREFIX . 'tenant_active_theme';
    private const TABLE_THEMES = self::TABLE_PREFIX . 'themes';
    private string $tablePrefix = self::TABLE_PREFIX;

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
            "INSERT INTO " . self::TABLE_THEMES . " (id, tenant_id, name, config, created_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $themeId,
            $tenantId,
            $themeName,
            json_encode($themeConfig),
            date('c'),
        ]);

        return $themeId;
    }

    public function getTheme(
        string $tenantId,
        string $themeId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_THEMES . " WHERE id = ? AND tenant_id = ?"
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
            "UPDATE " . self::TABLE_THEMES . " SET config = ?, updated_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([json_encode($themeConfig), date('c'), $themeId, $tenantId]);
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
            "UPDATE " . self::TABLE_TENANT_ACTIVE_THEME . " SET theme_id = ?, applied_at = ? WHERE tenant_id = ?"
        );

        $result = $stmt->execute([$themeId, date('c'), $tenantId]);

        if ($stmt->rowCount() === 0) {
            $insertStmt = $this->db->prepare(
                "INSERT INTO " . self::TABLE_TENANT_ACTIVE_THEME . " (tenant_id, theme_id, applied_at)
                 VALUES (?, ?, ?)"
            );

            return $insertStmt->execute([$tenantId, $themeId, date('c')]);
        }

        return $result;
    }

    public function getActiveTheme(
        string $tenantId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT t.* FROM " . self::TABLE_THEMES . " t
             INNER JOIN " . self::TABLE_TENANT_ACTIVE_THEME . " tat ON t.id = tat.theme_id
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
            "SELECT id, name, created_at FROM " . self::TABLE_THEMES . " WHERE tenant_id = ? ORDER BY created_at DESC"
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
            "DELETE FROM " . self::TABLE_THEMES . " WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([$themeId, $tenantId]);
    }
}
