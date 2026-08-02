<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class ConversationSummaryAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    
    public static function id(): string { return 'conversation-summary-agent'; }
    public static function name(): string { return 'Conversation Summary Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array
    {
        return [
            'Summarize conversations with key points',
            'Extract action items and decisions',
            'Identify customer sentiment and intent',
            'Create structured summaries by topic',
            'Link conversations to relevant records',
            'Generate follow-up actions automatically',
            'Support multiple languages',
            'Maintain conversation history'
        ];
    }
    public static function permissions(): array
    {
        return [
            'conversations.read',
            'conversations.summarize',
            'communications.read',
            'analytics.write'
        ];
    }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'conversations.summarize','atomic'=>true]; }
    
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        $conversationId = $input->conversationId ?? null;
        if (!$conversationId) throw new \InvalidArgumentException('ConversationSummaryAgent requires conversationId.');
        
        $result = $this->runtime->executeTool('conversations.summarize', ['conversation_id' => $conversationId], $context);
        return AgentActionResult::fromTool($result, 'conversation.summarized', ['CreateFollowUpAgent']);
    }
}
