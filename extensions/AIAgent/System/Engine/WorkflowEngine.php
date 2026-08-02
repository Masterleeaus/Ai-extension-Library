<?php

declare(strict_types=1);

namespace App\Extensions\AIAgent\System\Engine;

use App\Extensions\AIAgent\System\Connectors\ValueObjects\IncomingMessage;
use App\Extensions\AIAgent\System\Enums\TriggerTypeEnum;
use App\Extensions\AIAgent\System\Enums\WorkflowRunStatusEnum;
use App\Extensions\AIAgent\System\Enums\WorkflowStatusEnum;
use App\Extensions\AIAgent\System\Models\AIAgentChannel;
use App\Extensions\AIAgent\System\Models\AIAgentConversation;
use App\Extensions\AIAgent\System\Models\AIAgentWorkflow;
use App\Extensions\AIAgent\System\Models\AIAgentWorkflowRun;
use App\Extensions\AIAgent\System\Triggers\ChannelMessageTrigger;
use App\Extensions\AIAgent\System\Triggers\ScheduleTrigger;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WorkflowEngine
{
    public function __construct(
        private readonly ActionDispatcher $dispatcher,
        private readonly ScheduleTrigger $scheduleTrigger,
        private readonly ChannelMessageTrigger $channelMessageTrigger,
    ) {}

    /**
     * Poll all active schedule-triggered workflows and fire any that are due.
     */
    public function processScheduledWorkflows(?Command $command = null): int
    {
        $candidates = AIAgentWorkflow::query()
            ->where('status', WorkflowStatusEnum::Active)
            ->where('trigger_type', TriggerTypeEnum::Schedule)
            ->get();

        $command?->line("[AIAgent] Found {$candidates->count()} active scheduled workflow(s).");

        $fired = 0;

        foreach ($candidates as $workflow) {
            $due = $this->scheduleTrigger->shouldFire($workflow);

            $command?->line("[AIAgent] Workflow #{$workflow->id} \"{$workflow->name}\" — " . ($due ? 'due, firing...' : 'not due, skipping.'));
            Log::info('[AIAgent] Schedule check', [
                'workflow_id'   => $workflow->id,
                'workflow_name' => $workflow->name,
                'due'           => $due,
                'next_run_at'   => $workflow->next_run_at?->toIso8601String(),
            ]);

            if (! $due) {
                continue;
            }

            $triggerData = $this->scheduleTrigger->buildTriggerData($workflow);
            $run = $this->run($workflow, $triggerData);
            $this->updateNextRunAt($workflow);
            $fired++;

            $status = $run->status->value;
            $command?->{$status === 'info' || $status === 'completed' ? 'info' : 'error'}(
                "[AIAgent] Workflow #{$workflow->id} run #{$run->id} — {$status}."
            );

            if ($run->error_message) {
                $command?->error("[AIAgent] Error: {$run->error_message}");
            }
        }

        return $fired;
    }

    /**
     * Fire all active channel_message workflows that match the incoming message.
     */
    public function fireChannelMessageTrigger(AIAgentChannel $channel, IncomingMessage $message): void
    {
        $this->channelMessageTrigger->setContext($channel, $message);

        AIAgentWorkflow::query()
            ->where('user_id', $channel->user_id)
            ->where('status', WorkflowStatusEnum::Active)
            ->where('trigger_type', TriggerTypeEnum::ChannelMessage)
            ->get()
            ->filter(fn (AIAgentWorkflow $w) => $this->channelMessageTrigger->shouldFire($w))
            ->each(function (AIAgentWorkflow $workflow) use ($message): void {
                if ($message->conversationId) {
                    AIAgentConversation::query()
                        ->where('id', $message->conversationId)
                        ->whereNull('workflow_id')
                        ->update(['workflow_id' => $workflow->id]);
                }

                $triggerData = $this->channelMessageTrigger->buildTriggerData($workflow);

                $initialContext = [
                    'message_text'        => $message->text,
                    'sender_id'           => $message->senderId,
                    'conversation_id'     => $message->conversationId,
                    'message_attachments' => $message->attachments,
                ];

                $this->run($workflow, $triggerData, $initialContext);
            });
    }

    /**
     * Run all matching channel-message workflows from a web reply — connector sends suppressed.
     *
     * @return Collection<int, AIAgentWorkflowRun>
     */
    public function fireChannelMessageTriggerForWeb(AIAgentChannel $channel, IncomingMessage $message): Collection
    {
        $this->channelMessageTrigger->setContext($channel, $message);

        return AIAgentWorkflow::query()
            ->where('user_id', $channel->user_id)
            ->where('status', WorkflowStatusEnum::Active)
            ->where('trigger_type', TriggerTypeEnum::ChannelMessage)
            ->get()
            ->filter(fn (AIAgentWorkflow $w) => $this->channelMessageTrigger->shouldFire($w))
            ->map(function (AIAgentWorkflow $workflow) use ($message): AIAgentWorkflowRun {
                if ($message->conversationId) {
                    AIAgentConversation::query()
                        ->where('id', $message->conversationId)
                        ->whereNull('workflow_id')
                        ->update(['workflow_id' => $workflow->id]);
                }

                $triggerData = $this->channelMessageTrigger->buildTriggerData($workflow);

                $initialContext = [
                    'message_text'        => $message->text,
                    'sender_id'           => $message->senderId,
                    'conversation_id'     => $message->conversationId,
                    'message_attachments' => $message->attachments,
                    'web_reply_mode'      => true,
                    'web_channel_id'      => $channel->id,
                ];

                return $this->run($workflow, $triggerData, $initialContext);
            })
            ->values();
    }

    /**
     * Fire a webhook-triggered workflow directly (called from the webhook controller).
     */
    public function fireWebhookTrigger(AIAgentWorkflow $workflow, array $payload = []): void
    {
        $triggerData = [
            'trigger_type' => 'webhook',
            'fired_at'     => now()->toIso8601String(),
            'payload'      => $payload,
        ];

        $this->run($workflow, $triggerData, $payload);
    }

    /**
     * Execute a workflow: create a run record, execute each step in sequence.
     */
    public function run(
        AIAgentWorkflow $workflow,
        array $triggerData = [],
        array $initialContext = [],
    ): AIAgentWorkflowRun {
        /** @var AIAgentWorkflowRun $run */
        $run = AIAgentWorkflowRun::query()->create([
            'workflow_id'  => $workflow->id,
            'status'       => WorkflowRunStatusEnum::Running,
            'trigger_data' => $triggerData,
            'started_at'   => now(),
        ]);

        $context = array_merge($initialContext, $triggerData);
        $stepsOutput = [];

        try {
            $steps = $workflow->resolved_steps;

            foreach ($steps as $index => $stepDef) {
                $type = $stepDef['type'] ?? $stepDef['action'] ?? '';
                $config = $stepDef['config'] ?? [];
                $action = $this->dispatcher->resolve($type);
                $context = $action->execute($config, $context, $workflow, $run);

                $stepsOutput[$index] = [
                    'type'   => $type,
                    'status' => 'completed',
                    'output' => $context,
                ];
            }

            $run->update([
                'status'       => WorkflowRunStatusEnum::Completed,
                'steps_output' => $stepsOutput,
                'completed_at' => now(),
            ]);

            $workflow->update(['last_run_at' => now()]);
        } catch (Exception $e) {
            Log::error('AIAgent WorkflowEngine error', [
                'workflow_id' => $workflow->id,
                'run_id'      => $run->id,
                'error'       => $e->getMessage(),
            ]);

            $run->update([
                'status'        => WorkflowRunStatusEnum::Failed,
                'steps_output'  => $stepsOutput,
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);
        }

        return $run;
    }

    private function updateNextRunAt(AIAgentWorkflow $workflow): void
    {
        $cron = $workflow->trigger_config['cron'] ?? null;

        if (empty($cron)) {
            return;
        }

        try {
            $nextRun = $this->scheduleTrigger->nextRunAt($cron);
            $workflow->update(['next_run_at' => $nextRun]);
        } catch (Exception) {
            // Invalid cron — skip silently; was already logged in execute.
        }
    }
}
