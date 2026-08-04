<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface BrandingThemingContract
{
    public function createTheme(
        string $tenantId,
        string $themeName,
        array $themeConfig
    ): string;

    public function getTheme(
        string $tenantId,
        string $themeId
    ): ?array;

    public function updateTheme(
        string $tenantId,
        string $themeId,
        array $themeConfig
    ): bool;

    public function applyTheme(
        string $tenantId,
        string $themeId
    ): bool;

    public function getActiveTheme(
        string $tenantId
    ): ?array;

    public function listThemes(
        string $tenantId
    ): array;

    public function previewTheme(
        string $tenantId,
        string $themeId
    ): string;

    public function deleteTheme(
        string $tenantId,
        string $themeId
    ): bool;
}
