<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore\DTO;

use InvalidArgumentException;

final readonly class WorkerExecutionContext
{
    public function __construct(
        public int $companyId,
        public int $actorId,
        public string $workerId,
        public string $workerType,
        public int $tier,
        public ?string $delegatedByWorkerId = null,
        public ?string $conversationId = null,
        public ?string $workflowId = null,
        public string $vertical = 'cleaning',
        public ?string $serviceType = null,
        public string $channel = 'internal',
    ) {
        if ($companyId < 1 || $actorId < 1) {
            throw new InvalidArgumentException('Company and actor are required.');
        }
        if ($workerId === '' || ! in_array($tier, [0, 1, 2, 3, 4], true)) {
            throw new InvalidArgumentException('A valid worker ID and tier are required.');
        }
        if ($vertical !== 'cleaning') {
            throw new InvalidArgumentException('This runtime package is restricted to the cleaning vertical.');
        }
    }

    /** @return array<string,mixed> */
    public function metadata(): array
    {
        return array_filter([
            'source' => 'titan_ai.cleaning',
            'worker_id' => $this->workerId,
            'worker_type' => $this->workerType,
            'tier' => $this->tier,
            'delegated_by_worker_id' => $this->delegatedByWorkerId,
            'conversation_id' => $this->conversationId,
            'workflow_id' => $this->workflowId,
            'vertical' => $this->vertical,
            'service_type' => $this->serviceType,
            'channel' => $this->channel,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
