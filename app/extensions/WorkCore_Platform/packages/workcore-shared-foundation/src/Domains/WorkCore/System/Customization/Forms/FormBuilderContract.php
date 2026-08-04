<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Forms;

interface FormBuilderContract
{
    public function id(): string;
    public function tenantId(): int;
    public function name(): string;
    public function description(): ?string;
    public function fields(): array;
    public function steps(): array;
    public function conditionalLogic(): array;
    public function validation(): array;
    public function version(): int;
    public function isActive(): bool;
    public function completionMessage(): ?string;
    public function createdAt(): \DateTimeImmutable;
    public function updatedAt(): \DateTimeImmutable;
    public function validate(array $data): array; // Returns validation errors
    public function toArray(): array;
}
