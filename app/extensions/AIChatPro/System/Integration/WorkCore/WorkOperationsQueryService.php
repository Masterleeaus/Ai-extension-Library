<?php declare(strict_types=1);
namespace App\Extensions\AIChatPro\System\Integration\WorkCore;

/**
 * WorkCore WorkOperations Integration for AiChatPro
 * Issue #190: WorkOperations → AiChatPro Operations
 */
final class WorkOperationsQueryService extends BaseWorkCoreService
{
    public function listJobs(int $limit = 100): array
    {
        return $this->authorize('read', 'job') ? $this->list('job', $limit) : [];
    }

    public function getJob(string $jobId): ?array
    {
        return $this->authorize('read', 'job') ? null : null;
    }

    public function listSchedules(int $limit = 100): array
    {
        return $this->authorize('read', 'schedule') ? $this->list('schedule', $limit) : [];
    }

    public function getFleetStatus(): ?array
    {
        return $this->authorize('read', 'fleet') ? null : null;
    }
}
