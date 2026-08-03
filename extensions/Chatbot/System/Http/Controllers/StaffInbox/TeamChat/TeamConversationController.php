<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\TeamChat;

use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversation;
use App\Extensions\Chatbot\System\Policies\TeamChat\TeamConversationPolicy;
use App\Extensions\Chatbot\System\Services\TeamChat\TeamConversationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TeamConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $userId = (int) $request->user()->getAuthIdentifier();

        $conversations = TeamConversation::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId)->whereNull('left_at')->whereNull('archived_at'))
            ->with(['participants', 'latestMessage'])
            ->latest('updated_at')
            ->paginate(min(max((int) $request->integer('per_page', 30), 1), 100));

        return response()->json($conversations);
    }

    public function store(Request $request, TeamConversationService $service): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:direct,group'],
            'name' => ['nullable', 'string', 'max:255', 'required_if:type,group'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'device_id' => ['nullable', 'string', 'max:191'],
        ]);

        $conversation = $service->create(
            $this->tenantId($request),
            (int) $request->user()->getAuthIdentifier(),
            $data['type'],
            $data['name'] ?? null,
            $data['user_ids'],
            $data['device_id'] ?? null,
        );

        return response()->json($conversation, $conversation->wasRecentlyCreated ? 201 : 200);
    }


    public function update(Request $request, TeamConversation $conversation, TeamConversationPolicy $policy): JsonResponse
    {
        $this->authorizeAccess($request, $conversation);
        $actorId = (int) $request->user()->getAuthIdentifier();
        abort_unless($conversation->type === 'group' && $policy->canManage($conversation, $actorId), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ]);

        $conversation->fill($data);
        $conversation->updated_by = $actorId;
        $conversation->version = ((int) $conversation->version) + 1;
        $conversation->save();

        event(new \App\Extensions\Chatbot\System\Events\TeamChat\TeamConversationUpdated($conversation, 'metadata-updated', $actorId));

        return response()->json($conversation->fresh(['participants']));
    }

    public function show(Request $request, TeamConversation $conversation): JsonResponse
    {
        $this->authorizeAccess($request, $conversation);
        return response()->json($conversation->load(['participants', 'messages.reads']));
    }

    public function leave(Request $request, TeamConversation $conversation, TeamConversationService $service): JsonResponse
    {
        $this->authorizeAccess($request, $conversation);
        $service->leave($conversation, (int) $request->user()->getAuthIdentifier());
        return response()->json(['status' => 'success']);
    }

    public function archive(Request $request, TeamConversation $conversation, TeamConversationService $service): JsonResponse
    {
        $this->authorizeAccess($request, $conversation);
        $data = $request->validate(['archived' => ['required', 'boolean']]);
        $service->archiveForUser($conversation, (int) $request->user()->getAuthIdentifier(), (bool) $data['archived']);
        return response()->json(['status' => 'success']);
    }

    private function authorizeAccess(Request $request, TeamConversation $conversation): void
    {
        abort_unless($conversation->tenant_id === $this->tenantId($request), 404);
        abort_unless($conversation->hasParticipant((int) $request->user()->getAuthIdentifier()), 403);
    }

    private function tenantId(Request $request): int
    {
        $user = $request->user();
        return (int) ($user->tenant_id ?? $user->company_id ?? $user->getAuthIdentifier());
    }
}
