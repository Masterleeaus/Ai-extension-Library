<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\DTO;

final class TitanAIResponse
{
    /** @param array<string,mixed> $data */
    public function __construct(
        public readonly string $runUuid,
        public readonly string $status,
        public readonly bool $handled,
        public readonly ?string $message,
        public readonly array $data,
    ) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'run_uuid' => $this->runUuid,
            'status' => $this->status,
            'handled' => $this->handled,
            'message' => $this->message,
            ...$this->data,
        ];
    }
}
