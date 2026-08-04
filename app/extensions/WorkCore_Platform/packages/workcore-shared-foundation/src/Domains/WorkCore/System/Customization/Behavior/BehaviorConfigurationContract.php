<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Behavior;

use DateTimeImmutable;

interface BehaviorConfigurationContract
{
    public function id(): string;
    public function tenantId(): int;
    public function name(): string;
    public function modelTemperature(): float;
    public function topP(): float;
    public function maxTokens(): int;
    public function responseStyle(): string;
    public function tone(): string;
    public function personalityTraits(): array;
    public function guardrails(): array;
    public function contentFilter(): array;
    public function biasDetection(): bool;
    public function hallucinationPrevention(): bool;
    public function retryStrategy(): array;
    public function fallbackBehavior(): string;
    public function version(): int;
    public function isActive(): bool;
    public function createdAt(): DateTimeImmutable;
    public function updatedAt(): DateTimeImmutable;

    public function toArray(): array;
}
