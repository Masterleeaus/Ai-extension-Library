<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Branding;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class Theme implements ThemeContract
{
    private string $id;
    private int $version;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        private int $tenantId,
        private string $name,
        private string $primaryColor,
        private string $secondaryColor,
        private string $accentColor,
        private string $fontFamily = 'system-ui, -apple-system, sans-serif',
        private ?string $logoUrl = null,
        private ?string $faviconUrl = null,
        private ?string $customCSS = null,
        private bool $isDarkMode = false,
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
    }

    public function id(): string { return $this->id; }
    public function tenantId(): int { return $this->tenantId; }
    public function name(): string { return $this->name; }
    public function primaryColor(): string { return $this->primaryColor; }
    public function secondaryColor(): string { return $this->secondaryColor; }
    public function accentColor(): string { return $this->accentColor; }
    public function logoUrl(): ?string { return $this->logoUrl; }
    public function faviconUrl(): ?string { return $this->faviconUrl; }
    public function fontFamily(): string { return $this->fontFamily; }
    public function customCSS(): ?string { return $this->customCSS; }
    public function version(): int { return $this->version; }
    public function isActive(): bool { return $this->isActive; }
    public function isDarkMode(): bool { return $this->isDarkMode; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function toCSSVariables(): array
    {
        $isDark = $this->isDarkMode ? ' (dark mode)' : '';

        return [
            '--primary-color' => $this->primaryColor,
            '--secondary-color' => $this->secondaryColor,
            '--accent-color' => $this->accentColor,
            '--font-family' => $this->fontFamily,
            '--primary-rgb' => $this->colorToRGB($this->primaryColor),
            '--secondary-rgb' => $this->colorToRGB($this->secondaryColor),
            '--accent-rgb' => $this->colorToRGB($this->accentColor),
            '--logo-url' => $this->logoUrl ? "url('{$this->logoUrl}')" : 'none',
            '--favicon-url' => $this->faviconUrl ? "url('{$this->faviconUrl}')" : 'none',
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'name' => $this->name,
            'primaryColor' => $this->primaryColor,
            'secondaryColor' => $this->secondaryColor,
            'accentColor' => $this->accentColor,
            'logoUrl' => $this->logoUrl,
            'faviconUrl' => $this->faviconUrl,
            'fontFamily' => $this->fontFamily,
            'customCSS' => $this->customCSS,
            'version' => $this->version,
            'isActive' => $this->isActive,
            'isDarkMode' => $this->isDarkMode,
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c'),
        ];
    }

    public static function from(array $data): self
    {
        return new self(
            tenantId: $data['tenantId'] ?? $data['tenant_id'] ?? throw new \InvalidArgumentException('tenantId required'),
            name: $data['name'] ?? throw new \InvalidArgumentException('name required'),
            primaryColor: $data['primaryColor'] ?? $data['primary_color'] ?? throw new \InvalidArgumentException('primaryColor required'),
            secondaryColor: $data['secondaryColor'] ?? $data['secondary_color'] ?? throw new \InvalidArgumentException('secondaryColor required'),
            accentColor: $data['accentColor'] ?? $data['accent_color'] ?? throw new \InvalidArgumentException('accentColor required'),
            fontFamily: $data['fontFamily'] ?? $data['font_family'] ?? 'system-ui, -apple-system, sans-serif',
            logoUrl: $data['logoUrl'] ?? $data['logo_url'],
            faviconUrl: $data['faviconUrl'] ?? $data['favicon_url'],
            customCSS: $data['customCSS'] ?? $data['custom_css'],
            isDarkMode: (bool) ($data['isDarkMode'] ?? $data['is_dark_mode'] ?? false),
            id: $data['id'],
            version: $data['version'] ?? 1,
            isActive: $data['isActive'] ?? $data['is_active'] ?? true,
            createdAt: isset($data['createdAt']) ? new DateTimeImmutable($data['createdAt']) : null,
            updatedAt: isset($data['updatedAt']) ? new DateTimeImmutable($data['updatedAt']) : null,
        );
    }

    private function colorToRGB(string $color): string
    {
        $color = ltrim($color, '#');

        if (strlen($color) === 6) {
            $r = hexdec(substr($color, 0, 2));
            $g = hexdec(substr($color, 2, 2));
            $b = hexdec(substr($color, 4, 2));
            return "{$r}, {$g}, {$b}";
        }

        return '0, 0, 0';
    }
}
