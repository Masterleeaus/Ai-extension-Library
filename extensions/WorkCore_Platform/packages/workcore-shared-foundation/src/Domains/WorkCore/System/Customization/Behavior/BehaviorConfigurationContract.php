<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Behavior;

interface BehaviorConfigurationContract
{
    public function id(): string;

    public function tenantId(): int;

    public function vertical(): string; // aichatpro, chatbot, aiagent

    public function modelTemperature(): float; // 0.0 - 2.0

    public function topP(): float; // 0.0 - 1.0

    public function maxTokens(): int;

    public function responseStyle(): string; // formal, casual, technical, friendly

    public function tone(): string; // professional, conversational, educational

    public function personalityTraits(): array;

    public function guardrails(): array;

    public function contentFilter(): array;

    public function biasDetection(): bool;

    public function halluccinationPrevention(): bool;

    public function retryStrategy(): array;

    public function fallbackBehavior(): ?string;

    public function version(): int;

    public function isActive(): bool;

    public function createdAt(): \DateTimeImmutable;

    public function updatedAt(): \DateTimeImmutable;

    public function toArray(): array;
}
