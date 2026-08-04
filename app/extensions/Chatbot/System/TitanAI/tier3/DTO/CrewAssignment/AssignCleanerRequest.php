<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\DTO\CrewAssignment;

use InvalidArgumentException;

final readonly class AssignCleanerRequest
{
    public function __construct(
        public int $jobId,
        public int $cleanerId,
        public ?string $reason = null,
        public ?string $idempotencyKey = null,
        public ?string $confirmationId = null,
    ) {
        if ($jobId < 1 || $cleanerId < 1) throw new InvalidArgumentException('Job and cleaner are required.');
    }
    /** @return array<string,mixed> */
    public function toArray(): array { return ['job_id'=>$this->jobId,'cleaner_id'=>$this->cleanerId,'reason'=>$this->reason]; }
}
