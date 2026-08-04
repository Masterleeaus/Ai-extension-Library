<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class CustomerLoyaltyAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    
    public static function id(): string { return 'customer-loyalty-agent'; }
    public static function name(): string { return 'Customer Loyalty Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array
    {
        return [
            'Manage loyalty programs with tier progression',
            'Track reward points and redemption history',
            'Calculate loyalty tier eligibility',
            'Offer personalized incentives based on history',
            'Generate loyalty program reports',
            'Process reward redemptions',
            'Notify customers of loyalty milestones',
            'Apply birthday or anniversary bonuses'
        ];
    }
    public static function permissions(): array
    {
        return [
            'customers.write',
            'loyalty.write',
            'rewards.write',
            'rewards.read',
            'communications.send',
            'analytics.read'
        ];
    }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'loyalty.manage','atomic'=>true]; }
    
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        $customerId = $input->customerId ?? null;
        if (!$customerId) throw new \InvalidArgumentException('CustomerLoyaltyAgent requires customerId.');
        
        $result = $this->runtime->executeTool('loyalty.manage', ['customer_id' => $customerId], $context);
        return AgentActionResult::fromTool($result, 'loyalty.updated', ['SendCustomerMessageAgent']);
    }
}
