<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Http\Controllers;

use App\Extensions\TitanAIGovernance\System\Rollback\RollbackExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class GovernanceRollbackController extends Controller
{
    public function store(Request $request, string $receiptId, RollbackExecutionService $service): JsonResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $user = $request->user();
        $tenantId = (string) ($user?->company_id ?? $user?->tenant_id ?? '');
        abort_if(! $user || $tenantId === '' || (! method_exists($user, 'can') || ! $user->can('rollback-ai-actions')), 403);
        return response()->json($service->execute($receiptId, (string) $user->getAuthIdentifier(), $tenantId, $request->string('reason')->toString() ?: null));
    }
}
