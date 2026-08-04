<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Results;

use App\Domains\WorkCore\System\AI\DTO\AiToolResult;

final readonly class AgentActionResult
{
    /** @param list<string> $nextSteps */
    public function __construct(
        public bool $successful,
        public string $action,
        public array $data,
        public array $nextSteps = [],
        public ?string $auditId = null,
        public array $eventIds = [],
        public bool $replayed = false,
    ) {}

    /** @param list<string> $nextSteps */
    public static function fromTool(AiToolResult $result, string $action, array $nextSteps=[]): self
    {
        return new self(true,$action,$result->data,$nextSteps,$result->auditId,$result->eventIds,$result->replayed);
    }
}
