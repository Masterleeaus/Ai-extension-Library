<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\LocalizationContract;
use PDO;

class Localization implements LocalizationContract
{
    private PDO $db;
    private string $tablePrefix = 'localization_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function setLocale(
        string $tenantId,
        string $locale
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}tenant_locales (tenant_id, locale, set_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE locale = ?, updated_at = ?"
        );

        $now = date('c');

        return $stmt->execute([$tenantId, $locale, $now, $locale, $now]);
    }

    public function getLocale(
        string $tenantId
    ): string {
        $stmt = $this->db->prepare(
            "SELECT locale FROM {$this->tablePrefix}tenant_locales WHERE tenant_id = ?"
        );

        $stmt->execute([$tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['locale'] : 'en';
    }

    public function translateString(
        string $tenantId,
        string $key,
        array $parameters = []
    ): string {
        $locale = $this->getLocale($tenantId);

        $stmt = $this->db->prepare(
            "SELECT translation FROM {$this->tablePrefix}strings WHERE tenant_id = ? AND locale = ? AND key = ?"
        );

        $stmt->execute([$tenantId, $locale, $key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $translation = $result ? $result['translation'] : $key;

        foreach ($parameters as $paramKey => $paramValue) {
            $translation = str_replace(":{$paramKey}", (string)$paramValue, $translation);
        }

        return $translation;
    }

    public function getTranslations(
        string $tenantId,
        string $locale
    ): array {
        $stmt = $this->db->prepare(
            "SELECT key, translation FROM {$this->tablePrefix}strings WHERE tenant_id = ? AND locale = ?"
        );

        $stmt->execute([$tenantId, $locale]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $translations = [];

        foreach ($results as $result) {
            $translations[$result['key']] = $result['translation'];
        }

        return $translations;
    }

    public function setTranslation(
        string $tenantId,
        string $locale,
        string $key,
        string $translation
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}strings (tenant_id, locale, key, translation, set_at)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE translation = ?, updated_at = ?"
        );

        $now = date('c');

        return $stmt->execute([
            $tenantId,
            $locale,
            $key,
            $translation,
            $now,
            $translation,
            $now,
        ]);
    }

    public function listSupportedLocales(
        string $tenantId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT locale FROM {$this->tablePrefix}strings WHERE tenant_id = ? ORDER BY locale"
        );

        $stmt->execute([$tenantId]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'locale');
    }

    public function exportTranslations(
        string $tenantId,
        string $locale,
        string $format
    ): string {
        $translations = $this->getTranslations($tenantId, $locale);

        if ($format === 'json') {
            return json_encode($translations, JSON_PRETTY_PRINT);
        } elseif ($format === 'csv') {
            $csv = "key,translation\n";

            foreach ($translations as $key => $translation) {
                $csv .= "\"{$key}\",\"" . str_replace('"', '""', $translation) . "\"\n";
            }

            return $csv;
        }

        return '';
    }

    public function importTranslations(
        string $tenantId,
        string $locale,
        string $content,
        string $format
    ): bool {
        if ($format === 'json') {
            $translations = json_decode($content, true);

            if (!is_array($translations)) {
                return false;
            }

            foreach ($translations as $key => $translation) {
                $this->setTranslation($tenantId, $locale, $key, (string)$translation);
            }

            return true;
        }

        return false;
    }
}
