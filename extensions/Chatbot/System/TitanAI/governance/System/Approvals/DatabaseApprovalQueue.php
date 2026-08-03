<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Approvals;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DatabaseApprovalQueue implements ApprovalQueue
{
    public function enqueue(array $request): string
    {
        $id = (string) Str::uuid();
        DB::table('titan_ai_approval_requests')->insert([
            'id' => $id,
            'tenant_id' => (string) ($request['tenant_id'] ?? ''),
            'user_id' => (string) ($request['user_id'] ?? ''),
            'device_id' => (string) ($request['device_id'] ?? ''),
            'action' => (string) ($request['action'] ?? ''),
            'risk_level' => (string) ($request['risk_level'] ?? 'red'),
            'payload' => json_encode((array) ($request['payload'] ?? []), JSON_THROW_ON_ERROR),
            'council_result' => json_encode((array) ($request['council_result'] ?? []), JSON_THROW_ON_ERROR),
            'status' => 'pending',
            'expires_at' => $request['expires_at'] ?? now()->addHours(24),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $id;
    }

    public function resolve(string $approvalId, string $decision, string $actorId, ?string $note = null): void
    {
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Approval decision must be approved or rejected.');
        }

        DB::table('titan_ai_approval_requests')->where('id', $approvalId)->where('status', 'pending')->update([
            'status' => $decision,
            'resolved_by' => $actorId,
            'resolution_note' => $note,
            'resolved_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function find(string $approvalId): ?array
    {
        $row = DB::table('titan_ai_approval_requests')->where('id', $approvalId)->first();
        return $row ? (array) $row : null;
    }
}
