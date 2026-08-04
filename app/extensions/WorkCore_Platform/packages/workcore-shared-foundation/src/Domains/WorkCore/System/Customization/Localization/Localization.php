<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Localization;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class Localization implements LocalizationContract
{
    private string $id;
    private int $version;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private array $translations = [];
    private array $regionalSettings = [];

    private const RTL_LANGUAGES = ['ar', 'he', 'fa', 'ur'];
    private const CURRENCY_RATES = [
        'USD' => 1.0,
        'EUR' => 0.92,
        'GBP' => 0.79,
        'JPY' => 149.50,
        'AUD' => 1.52,
        'CAD' => 1.36,
    ];

    public function __construct(
        private int $tenantId,
        private string $language,
        private ?string $region = null,
        array $translations = [],
        array $regionalSettings = [],
        ?string $id = null,
        int $version = 1,
        bool $isActive = true,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id ?? Uuid::uuid4()->toString();
        $this->version = $version;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        $this->translations = $translations;
        $this->regionalSettings = $regionalSettings;
    }

    public function id(): string { return $this->id; }
    public function tenantId(): int { return $this->tenantId; }
    public function language(): string { return $this->language; }
    public function region(): ?string { return $this->region; }
    public function version(): int { return $this->version; }
    public function isActive(): bool { return $this->isActive; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function getSupportedLanguages(): array
    {
        return [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ja' => 'Japanese',
            'zh' => 'Chinese',
            'ar' => 'Arabic',
            'he' => 'Hebrew',
        ];
    }

    public function translate(string $key, array $params = []): ?string
    {
        $value = $this->translations[$key] ?? null;

        if ($value === null) {
            return null;
        }

        foreach ($params as $paramKey => $paramValue) {
            $value = str_replace('{{' . $paramKey . '}}', (string) $paramValue, $value);
            $value = str_replace('${' . $paramKey . '}', (string) $paramValue, $value);
        }

        return $value;
    }

    public function hasTranslation(string $key): bool
    {
        return isset($this->translations[$key]);
    }

    public function setTranslation(string $key, string $value): self
    {
        $this->translations[$key] = $value;
        return $this;
    }

    public function getRegionalSettings(): array
    {
        return $this->regionalSettings;
    }

    public function setRegionalSettings(array $settings): self
    {
        $this->regionalSettings = array_merge($this->regionalSettings, $settings);
        return $this;
    }

    public function detectLanguage(string $text): string
    {
        $textLower = strtolower($text);

        if (preg_match('/[\x{0600}-\x{06FF}]/u', $textLower)) {
            return 'ar';
        }
        if (preg_match('/[\x{0590}-\x{05FF}]/u', $textLower)) {
            return 'he';
        }
        if (preg_match('/[\x{4E00}-\x{9FFF}]/u', $textLower)) {
            return 'zh';
        }
        if (preg_match('/[\x{3040}-\x{309F}]/u', $textLower)) {
            return 'ja';
        }

        return 'en';
    }

    public function convertCurrency(float $amount, string $from, string $to): float
    {
        $fromRate = self::CURRENCY_RATES[$from] ?? 1.0;
        $toRate = self::CURRENCY_RATES[$to] ?? 1.0;

        return ($amount / $fromRate) * $toRate;
    }

    public function formatDate(string $date, string $format = 'default'): string
    {
        try {
            $dateTime = new DateTimeImmutable($date);

            return match ($this->language) {
                'de' => $dateTime->format('d.m.Y'),
                'fr' => $dateTime->format('d/m/Y'),
                'es', 'it', 'pt' => $dateTime->format('d/m/Y'),
                'ja' => $dateTime->format('Y年m月d日'),
                'zh' => $dateTime->format('Y年m月d日'),
                'ar' => $this->formatArabicDate($dateTime),
                default => $dateTime->format('m/d/Y'),
            };
        } catch (\Exception) {
            return $date;
        }
    }

    public function isRTL(): bool
    {
        return in_array($this->language, self::RTL_LANGUAGES, true);
    }

    private function formatArabicDate(DateTimeImmutable $dateTime): string
    {
        $arabicMonths = [
            '01' => 'يناير', '02' => 'فبراير', '03' => 'مارس',
            '04' => 'أبريل', '05' => 'مايو', '06' => 'يونيو',
            '07' => 'يوليو', '08' => 'أغسطس', '09' => 'سبتمبر',
            '10' => 'أكتوبر', '11' => 'نوفمبر', '12' => 'ديسمبر',
        ];

        $month = $dateTime->format('m');
        $day = $dateTime->format('d');
        $year = $dateTime->format('Y');

        return "{$day} {$arabicMonths[$month]} {$year}";
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'language' => $this->language,
            'region' => $this->region,
            'translations' => $this->translations,
            'regionalSettings' => $this->regionalSettings,
            'version' => $this->version,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c'),
        ];
    }

    public static function from(array $data): self
    {
        return new self(
            tenantId: $data['tenantId'] ?? $data['tenant_id'] ?? throw new \InvalidArgumentException('tenantId required'),
            language: $data['language'] ?? throw new \InvalidArgumentException('language required'),
            region: $data['region'],
            translations: $data['translations'] ?? [],
            regionalSettings: $data['regionalSettings'] ?? $data['regional_settings'] ?? [],
            id: $data['id'],
            version: $data['version'] ?? 1,
            isActive: $data['isActive'] ?? $data['is_active'] ?? true,
            createdAt: isset($data['createdAt']) ? new DateTimeImmutable($data['createdAt']) : null,
            updatedAt: isset($data['updatedAt']) ? new DateTimeImmutable($data['updatedAt']) : null,
        );
    }
}
