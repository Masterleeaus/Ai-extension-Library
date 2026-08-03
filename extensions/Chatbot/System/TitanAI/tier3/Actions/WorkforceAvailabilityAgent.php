<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class WorkforceAvailabilityAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    
    public static function id(): string { return 'workforce-availability-agent'; }
    public static function name(): string { return 'Workforce Availability Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array
    {
        return [
            'Check staff availability for a given date',
            'Track real-time cleaner availability',
            'Show detailed availability by skill and certification',
            'Find replacement cleaners for absent staff',
            'Predict availability trends',
            'Handle time-off requests',
            'Manage shift preferences',
            'Generate availability reports',
            'Support dynamic scheduling'
        ];
    }
    public static function permissions(): array
    {
        return [
            'workers.read',
            'schedule.read',
            'availability.read',
            'analytics.read',
            'reports.generate'
        ];
    }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'workforce.availability','atomic'=>true]; }
    
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        $date = $input->date ?? null;
        if (!$date) throw new \InvalidArgumentException('WorkforceAvailabilityAgent requires date.');
        
        $result = $this->runtime->executeTool('workforce.availability', ['date' => $date], $context);
        return AgentActionResult::fromTool($result, 'availability.checked', ['AssignCleanerAgent']);
    }
}
