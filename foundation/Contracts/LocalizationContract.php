<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface LocalizationContract
{
    public function setLocale(
        string $tenantId,
        string $locale
    ): bool;

    public function getLocale(
        string $tenantId
    ): string;

    public function translateString(
        string $tenantId,
        string $key,
        array $parameters = []
    ): string;

    public function getTranslations(
        string $tenantId,
        string $locale
    ): array;

    public function setTranslation(
        string $tenantId,
        string $locale,
        string $key,
        string $translation
    ): bool;

    public function listSupportedLocales(
        string $tenantId
    ): array;

    public function exportTranslations(
        string $tenantId,
        string $locale,
        string $format
    ): string;

    public function importTranslations(
        string $tenantId,
        string $locale,
        string $content,
        string $format
    ): bool;
}
