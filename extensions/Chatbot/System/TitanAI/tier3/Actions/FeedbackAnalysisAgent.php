<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class FeedbackAnalysisAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    
    public static function id(): string { return 'feedback-analysis-agent'; }
    public static function name(): string { return 'Feedback Analysis Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array
    {
        return [
            'Analyze customer feedback sentiment in detail',
            'Extract key themes and topics from feedback',
            'Identify improvement areas and opportunities',
            'Track sentiment trends over time',
            'Categorize feedback by service type',
            'Generate actionable insights',
            'Link feedback to operational metrics',
            'Provide real-time alerting for negative feedback'
        ];
    }
    public static function permissions(): array
    {
        return [
            'feedback.read',
            'analytics.read',
            'analytics.write',
            'communications.send'
        ];
    }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'feedback.analyze','atomic'=>true]; }
    
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        $feedbackId = $input->feedbackId ?? null;
        if (!$feedbackId) throw new \InvalidArgumentException('FeedbackAnalysisAgent requires feedbackId.');
        
        $result = $this->runtime->executeTool('feedback.analyze', ['feedback_id' => $feedbackId], $context);
        return AgentActionResult::fromTool($result, 'feedback.analyzed', ['RequestReviewAgent', 'SendCustomerMessageAgent']);
    }
}
