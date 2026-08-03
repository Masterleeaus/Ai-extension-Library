<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Rollback;

use App\Extensions\TitanAIGovernance\System\Contracts\WorkCoreGateway;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class RollbackExecutionService
{
    public function __construct(private WorkCoreGateway $workCore) {}

    public function execute(string $receiptId, string $actorId, string $tenantId, ?string $reason = null): array
    {
        return DB::transaction(function () use ($receiptId, $actorId, $tenantId, $reason): array {
            $receipt = DB::table('titan_ai_action_receipts')->where('id', $receiptId)->where('tenant_id', $tenantId)->lockForUpdate()->first();
            if (! $receipt) throw new RuntimeException('Action receipt was not found.');
            if (! in_array($receipt->status, ['executed', 'executed_after_approval'], true)) throw new RuntimeException('Only successfully executed actions can be rolled back.');
            $contract = json_decode((string) ($receipt->rollback_contract ?? ''), true);
            if (! is_array($contract) || ($contract['supported'] ?? false) !== true) throw new RuntimeException('This action does not support rollback.');
            $domain = (string) ($contract['domain'] ?? '');
            $operation = (string) ($contract['operation'] ?? '');
            if ($domain === '' || $operation === '' || empty($contract['authoritative_id'])) throw new RuntimeException('Rollback contract is incomplete.');

            DB::table('titan_ai_action_receipts')->where('id', $receiptId)->update(['status' => 'rollback_pending', 'updated_at' => now()]);
            $result = $this->workCore->execute($domain, $operation, [
                'authoritative_id' => $contract['authoritative_id'] ?? null,
                'original_receipt_id' => $receiptId,
                '_governance' => ['rollback' => true, 'approved_by' => $actorId, 'tenant_id' => $tenantId, 'reason' => $reason, 'idempotency_key' => 'rollback:'.$receiptId],
            ]);
            DB::table('titan_ai_action_receipts')->where('id', $receiptId)->update([
                'status' => 'rolled_back',
                'rollback_result' => json_encode($result, JSON_THROW_ON_ERROR),
                'rolled_back_by' => $actorId,
                'rolled_back_at' => now(),
                'updated_at' => now(),
            ]);
            return ['receipt_id' => $receiptId, 'status' => 'rolled_back', 'result' => $result];
        });
    }
}
