<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tools;

final readonly class SkillToolExecutionResult
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public string $status,
        public bool $succeeded,
        public bool $approvalRequired = false,
        public array $data = [],
        public ?string $message = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function success(array $data = []): self
    {
        return new self('completed', true, false, $data);
    }

    public static function denied(string $message): self
    {
        return new self('denied', false, false, [], $message);
    }

    public static function approval(string $message): self
    {
        return new self('approval_required', false, true, [], $message);
    }

    public static function unavailable(string $message): self
    {
        return new self('unavailable', false, false, [], $message);
    }

    public static function failed(string $message): self
    {
        return new self('failed', false, false, [], $message);
    }

    /** @return array{status: string, succeeded: bool, approval_required: bool, data: array<string, mixed>, message: ?string} */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'succeeded' => $this->succeeded,
            'approval_required' => $this->approvalRequired,
            'data' => $this->data,
            'message' => $this->message,
        ];
    }
}
