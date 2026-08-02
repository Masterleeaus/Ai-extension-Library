<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class AutomatedRescheduleAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    
    public static function id(): string { return 'automated-reschedule-agent'; }
    public static function name(): string { return 'Automated Reschedule Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array
    {
        return [
            'Auto-reschedule jobs based on availability',
            'Find optimal alternative time slots',
            'Minimize delays and conflicts',
            'Handle cleaner absence or resource unavailability',
            'Consider customer preferences for rescheduling',
            'Batch process multiple reschedules',
            'Resolve scheduling conflicts automatically',
            'Generate reschedule proposals for approval'
        ];
    }
    public static function permissions(): array
    {
        return [
            'jobs.read',
            'jobs.update',
            'schedule.read',
            'schedule.update',
            'communications.send'
        ];
    }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'schedule.auto-reschedule','atomic'=>true]; }
    
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        $jobId = $input->jobId ?? null;
        if (!$jobId) throw new \InvalidArgumentException('AutomatedRescheduleAgent requires jobId.');
        
        $result = $this->runtime->executeTool('schedule.auto-reschedule', ['job_id' => $jobId], $context);
        return AgentActionResult::fromTool($result, 'job.rescheduled', ['SendCustomerMessageAgent']);
    }
}
