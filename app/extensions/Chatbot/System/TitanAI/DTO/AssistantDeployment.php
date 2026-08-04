<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\DTO;

final class AssistantDeployment
{
    /** @param array<int,string> $capabilities @param array<string,mixed> $metadata */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $systemPrompt,
        public readonly ?string $provider,
        public readonly ?string $model,
        public readonly array $capabilities,
        public readonly array $metadata = [],
    ) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'system_prompt' => $this->systemPrompt,
            'provider' => $this->provider,
            'model' => $this->model,
            'capabilities' => $this->capabilities,
            'metadata' => $this->metadata,
        ];
    }
}
