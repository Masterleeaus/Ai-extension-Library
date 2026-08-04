<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Prompts;

interface PromptTemplateContract
{
    public function id(): string;
    public function tenantId(): int;
    public function name(): string;
    public function description(): ?string;
    public function category(): string;
    public function template(): string;
    public function variables(): array;
    public function version(): int;
    public function isActive(): bool;
    public function metadata(): array;
    public function createdAt(): \DateTimeImmutable;
    public function updatedAt(): \DateTimeImmutable;
    public function render(array $variables = []): string;
}
