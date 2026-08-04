<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Approvals;

use App\Extensions\TitanAIGovernance\System\Contracts\WorkCoreGateway;
use App\Extensions\TitanAIGovernance\System\Exceptions\WorkCoreExecutionFailed;
use Illuminate\Support\Facades\DB;
use Throwable;
use RuntimeException;

final class ApprovalExecutionService
{
    public function __construct(private ApprovalQueue $queue, private WorkCoreGateway $workCore) {}

    public function decide(string $approvalId, string $decision, string $actorId, string $tenantId, ?string $note = null): array
    {
        if ($decision === 'rejected') {
            $this->queue->resolve($approvalId, 'rejected', $actorId, $note, $tenantId);
            $this->updateReceipt($approvalId, 'rejected', null, null);
            return ['approval_id' => $approvalId, 'status' => 'rejected'];
        }
        if ($decision !== 'approved') throw new RuntimeException('Unsupported approval decision.');

        $stored = $this->queue->claimForExecution($approvalId, $actorId, $tenantId, $note);
        $payload = $this->decode($stored['payload'] ?? '{}');
        $tool = $this->decode($stored['tool_definition'] ?? '{}');
        $context = $this->decode($stored['execution_context'] ?? '{}');
        $domain = (string) ($stored['domain'] ?? $tool['domain'] ?? '');
        $operation = (string) ($stored['action'] ?? $tool['operation'] ?? '');
        if ($domain === '' || $operation === '') {
            $this->queue->failExecution($approvalId, $tenantId, 'Approval lacks an executable WorkCore domain or operation.');
            throw new WorkCoreExecutionFailed('Approval lacks an executable WorkCore domain or operation.');
        }

        try {
            $result = $this->workCore->execute($domain, $operation, [...$payload, '_governance' => [
                'approval_id' => $approvalId, 'approved_by' => $actorId, 'tenant_id' => $tenantId,
                'user_id' => (string) ($stored['user_id'] ?? ''), 'device_id' => (string) ($stored['device_id'] ?? ''),
                'idempotency_key' => $context['idempotency_key'] ?? ('approval:'.$approvalId),
            ]]);
            $this->queue->completeExecution($approvalId, $tenantId);
            $this->updateReceipt($approvalId, 'executed_after_approval', $result, null);
            return ['approval_id' => $approvalId, 'status' => 'executed', 'result' => $result];
        } catch (Throwable $e) {
            $this->queue->failExecution($approvalId, $tenantId, $e->getMessage());
            $this->updateReceipt($approvalId, 'execution_failed', null, $e->getMessage());
            throw new WorkCoreExecutionFailed('WorkCore execution failed; the approval remains retryable.', 0, $e);
        }
    }

    private function updateReceipt(string $approvalId, string $status, ?array $result, ?string $failure): void
    {
        DB::table('titan_ai_action_receipts')->where('approval_id', $approvalId)->update([
            'status' => $status,
            'workcore_result' => $result === null ? null : json_encode($result, JSON_THROW_ON_ERROR),
            'failure_reason' => $failure === null ? null : mb_substr($failure, 0, 2000),
            'updated_at' => now(),
        ]);
    }

    private function decode(mixed $value): array
    {
        if (is_array($value)) return $value;
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
