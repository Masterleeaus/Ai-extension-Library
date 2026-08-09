<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Services;

use App\Extensions\Migration\System\Enums\MigrationRunState;
use App\Extensions\Migration\System\Models\MigrationRun;
use LogicException;

final class MigrationRunStateMachine
{
    public function transition(MigrationRun $run, MigrationRunState $target): MigrationRun
    {
        $current = $run->state instanceof MigrationRunState
            ? $run->state
            : MigrationRunState::from((string) $run->state);

        if (! $current->canTransitionTo($target)) {
            throw new LogicException(sprintf(
                'Invalid migration run transition from %s to %s.',
                $current->value,
                $target->value,
            ));
        }

        $run->state = $target;

        match ($target) {
            MigrationRunState::QUEUED => $run->queued_at ??= now(),
            MigrationRunState::RUNNING => $run->started_at ??= now(),
            MigrationRunState::COMPLETED => $run->completed_at ??= now(),
            MigrationRunState::FAILED => $run->failed_at ??= now(),
            MigrationRunState::CANCELLED => $run->cancelled_at ??= now(),
            MigrationRunState::ROLLED_BACK => $run->rolled_back_at ??= now(),
            default => null,
        };

        $run->save();

        return $run->refresh();
    }
}
