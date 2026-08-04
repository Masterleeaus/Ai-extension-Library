<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Templates;

interface TemplateContract
{
    public function id(): string;
    public function tenantId(): int;
    public function name(): string;
    public function type(): string; // response, document, email, form, notification
    public function content(): string;
    public function variables(): array;
    public function version(): int;
    public function isActive(): bool;
    public function createdAt(): \DateTimeImmutable;
    public function updatedAt(): \DateTimeImmutable;
    public function render(array $variables = []): string;
}
