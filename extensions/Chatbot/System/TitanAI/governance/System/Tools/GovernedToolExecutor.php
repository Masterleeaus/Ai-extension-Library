<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Tools;

use App\Extensions\TitanAIGovernance\System\Approvals\ApprovalQueue;
use App\Extensions\TitanAIGovernance\System\Contracts\WorkCoreGateway;
use App\Extensions\TitanAIGovernance\System\Council\CouncilCase;
use App\Extensions\TitanAIGovernance\System\Council\OperationalCouncil;
use App\Extensions\TitanAIGovernance\System\Council\RiskClassifier;
use App\Extensions\TitanAIGovernance\System\Exceptions\GovernanceBlocked;
use App\Extensions\TitanAIGovernance\System\Receipts\ActionReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class GovernedToolExecutor
{
    public function __construct(
        private WorkCoreGateway $workCore,
        private OperationalCouncil $council,
        private RiskClassifier $classifier,
        private ApprovalQueue $approvals,
    ) {}

    public function execute(GovernedToolDefinition $tool, array $payload, array $context): array
    {
        $risk = $this->classifier->classify($tool->operation, ['risk_level' => $tool->riskLevel]);
        $case = new CouncilCase(
            $tool->operation,
            $payload,
            (array) ($context['facts'] ?? []),
            (array) ($context['constraints'] ?? []),
            $risk,
            (string) ($context['tenant_id'] ?? ''),
            (string) ($context['user_id'] ?? ''),
            (string) ($context['device_id'] ?? ''),
            $context['idempotency_key'] ?? null,
        );
        $decision = $this->council->deliberate($case);

        if (($decision['approval_required'] ?? false) === true || ($decision['decision'] ?? null) === 'human_review') {
            $approvalId = $this->approvals->enqueue([
                'tenant_id' => $case->tenantId,
                'user_id' => $case->userId,
                'device_id' => $case->deviceId,
                'action' => $tool->operation,
                'risk_level' => $risk->value,
                'payload' => $payload,
                'council_result' => $decision,
            ]);
            $this->persistReceipt(new ActionReceipt((string) Str::uuid(), $tool->operation, 'approval_required', $risk->value, $decision, $approvalId, null, null), $case, $context);
            throw new GovernanceBlocked('human_review', $approvalId, 'Governed action requires human approval.');
        }

        if (($decision['decision'] ?? null) !== 'approve') {
            $this->persistReceipt(new ActionReceipt((string) Str::uuid(), $tool->operation, 'blocked', $risk->value, $decision, null, null, null), $case, $context);
            throw new GovernanceBlocked((string) ($decision['decision'] ?? 'unknown'));
        }

        $result = $this->workCore->execute($tool->domain, $tool->operation, [
            ...$payload,
            '_governance' => [
                'risk_level' => $risk->value,
                'council' => $decision,
                'tenant_id' => $case->tenantId,
                'user_id' => $case->userId,
                'device_id' => $case->deviceId,
                'idempotency_key' => $case->idempotencyKey,
                'manager' => $context['manager'] ?? null,
                'assistant' => $context['assistant'] ?? null,
                'agent' => $context['agent'] ?? null,
                'conversation_id' => $context['conversation_id'] ?? null,
                'workflow_id' => $context['workflow_id'] ?? null,
                'channel' => $context['channel'] ?? null,
                'agent_execution_path' => $context['agent_execution_path'] ?? null,
            ],
        ]);

        $rollback = $tool->rollbackSupported ? [
            'supported' => true,
            'domain' => $tool->domain,
            'operation' => 'rollback_'.$tool->operation,
            'authoritative_id' => $result['id'] ?? $result['job_id'] ?? null,
        ] : null;
        $receipt = new ActionReceipt((string) Str::uuid(), $tool->operation, 'executed', $risk->value, $decision, null, $result, $rollback);
        $this->persistReceipt($receipt, $case, $context);

        return [...$result, '_action_receipt' => $receipt->toArray()];
    }

    private function persistReceipt(ActionReceipt $receipt, CouncilCase $case, array $context): void
    {
        DB::table('titan_ai_action_receipts')->insert([
            'id' => $receipt->receiptId,
            'tenant_id' => $case->tenantId,
            'user_id' => $case->userId,
            'device_id' => $case->deviceId,
            'action' => $receipt->action,
            'manager_id' => $context['manager'] ?? null,
            'assistant_id' => $context['assistant'] ?? null,
            'agent_id' => $context['agent'] ?? null,
            'conversation_id' => $context['conversation_id'] ?? null,
            'workflow_id' => $context['workflow_id'] ?? null,
            'channel' => $context['channel'] ?? null,
            'idempotency_key' => $case->idempotencyKey,
            'status' => $receipt->status,
            'risk_level' => $receipt->riskLevel,
            'approval_id' => $receipt->approvalId,
            'council_result' => json_encode($receipt->council, JSON_THROW_ON_ERROR),
            'workcore_result' => $receipt->workCoreResult === null ? null : json_encode($receipt->workCoreResult, JSON_THROW_ON_ERROR),
            'rollback_contract' => $receipt->rollback === null ? null : json_encode($receipt->rollback, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
