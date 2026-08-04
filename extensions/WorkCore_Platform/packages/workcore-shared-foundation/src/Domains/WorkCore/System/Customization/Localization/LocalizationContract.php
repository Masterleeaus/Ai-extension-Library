<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Localization;

interface LocalizationContract
{
    public function getSupportedLanguages(): array;

    public function translate(string $key, string $language, array $variables = []): ?string;

    public function hasTranslation(string $key, string $language): bool;

    public function setTranslation(int $tenantId, string $key, string $language, string $value): void;

    public function getRegionalSettings(int $tenantId, string $region): array;

    public function setRegionalSettings(int $tenantId, string $region, array $settings): void;

    public function detectLanguage(string $content): string;

    public function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): float;

    public function formatDate(\DateTimeImmutable $date, string $language): string;

    public function isRTL(string $language): bool;
}
