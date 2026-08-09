<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Enums;

enum MigrationRunState: string
{
    case DRAFT = 'draft';
    case READY = 'ready';
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLING = 'cancelling';
    case CANCELLED = 'cancelled';
    case ROLLBACK_QUEUED = 'rollback_queued';
    case ROLLING_BACK = 'rolling_back';
    case ROLLED_BACK = 'rolled_back';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::READY, self::CANCELLED],
            self::READY => [self::QUEUED, self::CANCELLED],
            self::QUEUED => [self::RUNNING, self::CANCELLED],
            self::RUNNING => [self::PAUSED, self::COMPLETED, self::FAILED, self::CANCELLING],
            self::PAUSED => [self::QUEUED, self::FAILED, self::CANCELLING],
            self::FAILED => [self::QUEUED, self::ROLLBACK_QUEUED],
            self::CANCELLING => [self::CANCELLED, self::FAILED],
            self::COMPLETED => [self::ROLLBACK_QUEUED],
            self::ROLLBACK_QUEUED => [self::ROLLING_BACK, self::FAILED],
            self::ROLLING_BACK => [self::ROLLED_BACK, self::FAILED],
            self::CANCELLED, self::ROLLED_BACK => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }
}
