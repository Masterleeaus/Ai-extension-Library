<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class ComplianceAuditAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    
    public static function id(): string { return 'compliance-audit-agent'; }
    public static function name(): string { return 'Compliance Audit Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array
    {
        return [
            'Audit compliance with regulations and policies',
            'Check regulatory requirements across domains',
            'Generate detailed audit reports',
            'Track compliance status over time',
            'Identify compliance gaps and risks',
            'Support audit evidence management',
            'Schedule recurring audits',
            'Provide remediation recommendations'
        ];
    }
    public static function permissions(): array
    {
        return [
            'compliance.read',
            'compliance.write',
            'audits.create',
            'audits.read',
            'reports.generate'
        ];
    }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'compliance.audit','atomic'=>true]; }
    
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        $auditType = $input->auditType ?? 'general';
        
        $result = $this->runtime->executeTool('compliance.audit', ['audit_type' => $auditType], $context);
        return AgentActionResult::fromTool($result, 'compliance.audited', []);
    }
}
