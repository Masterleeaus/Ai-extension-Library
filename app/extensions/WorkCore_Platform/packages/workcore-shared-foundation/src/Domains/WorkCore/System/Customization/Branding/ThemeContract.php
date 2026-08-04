<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Branding;

use DateTimeImmutable;

interface ThemeContract
{
    public function id(): string;
    public function tenantId(): int;
    public function name(): string;
    public function primaryColor(): string;
    public function secondaryColor(): string;
    public function accentColor(): string;
    public function logoUrl(): ?string;
    public function faviconUrl(): ?string;
    public function fontFamily(): string;
    public function customCSS(): ?string;
    public function version(): int;
    public function isActive(): bool;
    public function isDarkMode(): bool;
    public function createdAt(): DateTimeImmutable;
    public function updatedAt(): DateTimeImmutable;

    public function toCSSVariables(): array;
    public function toArray(): array;
}
