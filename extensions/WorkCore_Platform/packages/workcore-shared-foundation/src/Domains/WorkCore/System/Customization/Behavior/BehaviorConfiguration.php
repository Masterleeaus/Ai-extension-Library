<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Behavior;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class BehaviorConfiguration implements BehaviorConfigurationContract
{
    private string $id;
    private int $version;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        private int $tenantId,
        private string $name,
        private float $modelTemperature = 0.7,
        private float $topP = 1.0,
        private int $maxTokens = 2048,
        private string $responseStyle = 'conversational',
        private string $tone = 'professional',
        private array $personalityTraits = [],
        private array $guardrails = [],
        private array $contentFilter = [],
        private bool $biasDetection = true,
        private bool $hallucinationPrevention = true,
        private array $retryStrategy = [],
        private string $fallbackBehavior = 'graceful_degradation',
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

        if (empty($this->retryStrategy)) {
            $this->retryStrategy = [
                'maxAttempts' => 3,
                'initialDelayMs' => 100,
                'backoffMultiplier' => 2.0,
                'maxDelayMs' => 10000,
            ];
        }

        if (empty($this->contentFilter)) {
            $this->contentFilter = [
                'violence' => false,
                'hate' => false,
                'sexual' => false,
                'self_harm' => false,
            ];
        }
    }

    public function id(): string { return $this->id; }
    public function tenantId(): int { return $this->tenantId; }
    public function name(): string { return $this->name; }
    public function modelTemperature(): float { return $this->modelTemperature; }
    public function topP(): float { return $this->topP; }
    public function maxTokens(): int { return $this->maxTokens; }
    public function responseStyle(): string { return $this->responseStyle; }
    public function tone(): string { return $this->tone; }
    public function personalityTraits(): array { return $this->personalityTraits; }
    public function guardrails(): array { return $this->guardrails; }
    public function contentFilter(): array { return $this->contentFilter; }
    public function biasDetection(): bool { return $this->biasDetection; }
    public function hallucinationPrevention(): bool { return $this->hallucinationPrevention; }
    public function retryStrategy(): array { return $this->retryStrategy; }
    public function fallbackBehavior(): string { return $this->fallbackBehavior; }
    public function version(): int { return $this->version; }
    public function isActive(): bool { return $this->isActive; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'name' => $this->name,
            'modelTemperature' => $this->modelTemperature,
            'topP' => $this->topP,
            'maxTokens' => $this->maxTokens,
            'responseStyle' => $this->responseStyle,
            'tone' => $this->tone,
            'personalityTraits' => $this->personalityTraits,
            'guardrails' => $this->guardrails,
            'contentFilter' => $this->contentFilter,
            'biasDetection' => $this->biasDetection,
            'hallucinationPrevention' => $this->hallucinationPrevention,
            'retryStrategy' => $this->retryStrategy,
            'fallbackBehavior' => $this->fallbackBehavior,
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
            name: $data['name'] ?? throw new \InvalidArgumentException('name required'),
            modelTemperature: (float) ($data['modelTemperature'] ?? $data['model_temperature'] ?? 0.7),
            topP: (float) ($data['topP'] ?? $data['top_p'] ?? 1.0),
            maxTokens: (int) ($data['maxTokens'] ?? $data['max_tokens'] ?? 2048),
            responseStyle: $data['responseStyle'] ?? $data['response_style'] ?? 'conversational',
            tone: $data['tone'] ?? 'professional',
            personalityTraits: $data['personalityTraits'] ?? $data['personality_traits'] ?? [],
            guardrails: $data['guardrails'] ?? [],
            contentFilter: $data['contentFilter'] ?? $data['content_filter'] ?? [],
            biasDetection: (bool) ($data['biasDetection'] ?? $data['bias_detection'] ?? true),
            hallucinationPrevention: (bool) ($data['hallucinationPrevention'] ?? $data['hallucination_prevention'] ?? true),
            retryStrategy: $data['retryStrategy'] ?? $data['retry_strategy'] ?? [],
            fallbackBehavior: $data['fallbackBehavior'] ?? $data['fallback_behavior'] ?? 'graceful_degradation',
            id: $data['id'],
            version: $data['version'] ?? 1,
            isActive: $data['isActive'] ?? $data['is_active'] ?? true,
            createdAt: isset($data['createdAt']) ? new DateTimeImmutable($data['createdAt']) : null,
            updatedAt: isset($data['updatedAt']) ? new DateTimeImmutable($data['updatedAt']) : null,
        );
    }
}
