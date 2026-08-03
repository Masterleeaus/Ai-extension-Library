<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\AI\DTO;

use InvalidArgumentException;

final readonly class ModelMessage
{
    /** @param array<string,mixed> $metadata */
    public function __construct(
        public string $role,
        public string $content,
        public ?string $name = null,
        public ?string $toolCallId = null,
        public array $metadata = [],
    ) {
        if (! in_array($role, ['system', 'user', 'assistant', 'tool'], true)) {
            throw new InvalidArgumentException("Invalid model-message role [{$role}].");
        }
        if ($content === '' && $role !== 'assistant') {
            throw new InvalidArgumentException('Model-message content is required.');
        }
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $message = ['role' => $this->role, 'content' => $this->content];
        if ($this->name !== null && $this->name !== '') {
            $message['name'] = $this->name;
        }
        if ($this->toolCallId !== null && $this->toolCallId !== '') {
            $message['tool_call_id'] = $this->toolCallId;
        }

        return $message;
    }
}
