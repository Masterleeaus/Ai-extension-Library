<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Localization;

use DateTimeImmutable;

interface LocalizationContract
{
    public function id(): string;
    public function tenantId(): int;
    public function language(): string;
    public function region(): ?string;
    public function version(): int;
    public function isActive(): bool;
    public function createdAt(): DateTimeImmutable;
    public function updatedAt(): DateTimeImmutable;

    public function getSupportedLanguages(): array;
    public function translate(string $key, array $params = []): ?string;
    public function hasTranslation(string $key): bool;
    public function setTranslation(string $key, string $value): self;
    public function getRegionalSettings(): array;
    public function setRegionalSettings(array $settings): self;
    public function detectLanguage(string $text): string;
    public function convertCurrency(float $amount, string $from, string $to): float;
    public function formatDate(string $date, string $format = 'default'): string;
    public function isRTL(): bool;
    public function toArray(): array;
}
