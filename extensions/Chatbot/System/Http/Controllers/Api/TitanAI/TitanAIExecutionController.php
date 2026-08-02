<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers\Api\TitanAI;

use App\Extensions\Chatbot\System\TitanAI\Contracts\TitanAIRuntimeContract;
use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

final class TitanAIExecutionController extends Controller
{
    public function __construct(private readonly TitanAIRuntimeContract $runtime) {}

    public function execute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required','string','max:8000'],
            'messages' => ['nullable','array'],
            'payload' => ['nullable','array'],
            'attachments' => ['nullable','array'],
            'tool_permissions' => ['nullable','array'],
            'device_id' => ['required','string','max:128'],
            'conversation_id' => ['nullable','string','max:128'],
            'conversation_source' => ['nullable','string','max:64'],
            'assistant_deployment_id' => ['nullable','string','max:128'],
            'template' => ['nullable','string','max:128'],
            'workflow_id' => ['nullable','string','max:128'],
            'idempotency_key' => ['nullable','string','max:128'],
            'response_mode' => ['nullable','in:json,stream'],
        ]);
        $user = $request->user();
        $tenantId = (string) ($user->tenant_id ?? $user->company_id ?? '');
        $availablePermissions = $this->permissionSnapshot($user);

        try {
            $result = $this->runtime->execute(new TitanAIRequest(
                message: $validated['message'],
                tenantId: $tenantId,
                userId: (string) $user->getAuthIdentifier(),
                deviceId: $validated['device_id'],
                channel: 'chatbot-pwa',
                conversationSource: $validated['conversation_source'] ?? 'chatbot',
                conversationId: $validated['conversation_id'] ?? null,
                assistantDeploymentId: $validated['assistant_deployment_id'] ?? null,
                template: $validated['template'] ?? null,
                workflowId: $validated['workflow_id'] ?? null,
                idempotencyKey: $validated['idempotency_key'] ?? (string) Str::uuid(),
                responseMode: $validated['response_mode'] ?? 'json',
                messages: (array) ($validated['messages'] ?? []),
                payload: (array) ($validated['payload'] ?? []),
                attachments: (array) ($validated['attachments'] ?? []),
                toolPermissions: array_values((array) ($validated['tool_permissions'] ?? [])),
                availablePermissions: $availablePermissions,
            ));

            return response()->json($result->toArray(), $result->status === 'needs_review' ? 202 : 200);
        } catch (Throwable $error) {
            report($error);
            return response()->json(['handled'=>false,'status'=>'failed','error'=>'Titan AI execution failed safely.'], 422);
        }
    }
    /** @return list<string> */
    private function permissionSnapshot(object $user): array
    {
        if (method_exists($user, 'hasRole')) {
            try {
                if ($user->hasRole('super-admin') || $user->hasRole('admin')) return ['*'];
            } catch (Throwable) {
                // Fall through to explicit permissions.
            }
        }

        $permissions = [];
        if (method_exists($user, 'getAllPermissions')) {
            try {
                foreach ($user->getAllPermissions() as $permission) {
                    $name = is_object($permission) ? ($permission->name ?? null) : $permission;
                    if (is_string($name) && $name !== '') $permissions[] = $name;
                }
            } catch (Throwable) {
                // Host permission package may not be initialised in every context.
            }
        } elseif (isset($user->permissions)) {
            foreach ((array) $user->permissions as $permission) {
                $name = is_object($permission) ? ($permission->name ?? null) : $permission;
                if (is_string($name) && $name !== '') $permissions[] = $name;
            }
        }

        return array_values(array_unique($permissions));
    }

}
