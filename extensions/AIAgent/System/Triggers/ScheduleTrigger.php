<?php

declare(strict_types=1);

namespace App\Extensions\AIAgent\System\Triggers;

use App\Extensions\AIAgent\System\Models\AIAgentMessage;
use App\Extensions\AIAgent\System\Models\AIAgentWorkflow;
use App\Extensions\AIAgent\System\Triggers\Contracts\TriggerInterface;
use Cron\CronExpression;

class ScheduleTrigger implements TriggerInterface
{
    public function shouldFire(AIAgentWorkflow $workflow): bool
    {
        $config = $workflow->trigger_config ?? [];
        $cron   = $config['cron'] ?? null;

        if (empty($cron)) {
            return false;
        }

        // Never ran or next_run_at is in the past
        if ($workflow->next_run_at === null || $workflow->next_run_at->isPast()) {
            return true;
        }

        return false;
    }

    public function buildTriggerData(AIAgentWorkflow $workflow): array
    {
        $data = [
            'trigger_type' => 'schedule',
            'fired_at'     => now()->toIso8601String(),
            'cron'         => $workflow->trigger_config['cron'] ?? null,
        ];

        // Resolve sender_id from the last inbound message on the send_message step's channel,
        // so {sender_id} works as a recipient template in schedule-triggered workflows.
        $sendStep = collect($workflow->actions ?? [])
            ->first(fn (array $a) => ($a['type'] ?? '') === 'send_message');

        if ($sendStep && ! empty($sendStep['config']['channel_id'])) {
            $senderId = AIAgentMessage::query()
                ->where('channel_id', (int) $sendStep['config']['channel_id'])
                ->where('direction', 'inbound')
                ->latest('id')
                ->value('sender_id');

            if ($senderId) {
                $data['sender_id'] = $senderId;
            }
        }

        return $data;
    }

    /**
     * Calculate the next fire time from a cron expression.
     */
    public function nextRunAt(string $cron): \Carbon\Carbon
    {
        $expression = new CronExpression($cron);

        return \Carbon\Carbon::instance($expression->getNextRunDate());
    }
}
