<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Http\Controllers;

use App\Extensions\TitanAIGovernance\System\Approvals\ApprovalExecutionService;
use App\Extensions\TitanAIGovernance\System\Approvals\ApprovalQueue;
use App\Extensions\TitanAIGovernance\System\Exceptions\ApprovalConflict;
use App\Extensions\TitanAIGovernance\System\Exceptions\WorkCoreExecutionFailed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class GovernanceApprovalController extends Controller
{
    public function index(Request $request, ApprovalQueue $queue)
    {
        [$user, $tenantId] = $this->authorisedActor($request);
        $queue->expirePending();
        $approvals = $queue->pending($tenantId);
        return $request->expectsJson() ? response()->json(['data' => $approvals]) : view('titan-ai-governance::approvals.index', compact('approvals'));
    }

    public function approve(Request $request, string $approvalId, ApprovalExecutionService $service): JsonResponse
    {
        return $this->decide($request, $approvalId, 'approved', $service);
    }

    public function reject(Request $request, string $approvalId, ApprovalExecutionService $service): JsonResponse
    {
        return $this->decide($request, $approvalId, 'rejected', $service);
    }

    private function decide(Request $request, string $approvalId, string $decision, ApprovalExecutionService $service): JsonResponse
    {
        $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        [$user, $tenantId] = $this->authorisedActor($request);
        try {
            $result = $service->decide($approvalId, $decision, (string) $user->getAuthIdentifier(), $tenantId, $request->string('note')->toString() ?: null);
            return response()->json($result);
        } catch (ApprovalConflict $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'approval_conflict'], 409);
        } catch (WorkCoreExecutionFailed $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'workcore_execution_failed', 'retryable' => true], 502);
        }
    }

    private function authorisedActor(Request $request): array
    {
        $user = $request->user();
        $tenantId = (string) ($user?->company_id ?? $user?->tenant_id ?? '');
        abort_if(! $user || $tenantId === '' || ! method_exists($user, 'can') || ! $user->can('govern-ai-actions'), 403);
        return [$user, $tenantId];
    }
}
