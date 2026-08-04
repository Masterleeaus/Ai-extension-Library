<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\TeamChat;

use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversation;
use App\Extensions\Chatbot\System\Policies\TeamChat\TeamConversationPolicy;
use App\Extensions\Chatbot\System\Services\TeamChat\BusinessChannelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BusinessChannelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $userId = (int) $request->user()->getAuthIdentifier();
        $query = TeamConversation::query()->where('tenant_id', $tenantId)->where('type', 'channel')->whereNull('channel_archived_at');
        if (! $request->boolean('discover')) {
            $query->whereHas('participants', fn ($q) => $q->where('user_id', $userId)->whereNull('left_at'));
        } else {
            $query->where('visibility', 'public');
        }
        if ($request->filled('category')) $query->where('category', $request->string('category'));
        if ($request->filled('linked_entity_type')) $query->where('linked_entity_type', $request->string('linked_entity_type'));
        if ($request->filled('linked_entity_id')) $query->where('linked_entity_id', (string) $request->input('linked_entity_id'));
        return response()->json($query->with(['participants','latestMessage'])->latest('updated_at')->paginate(min(max($request->integer('per_page', 30), 1), 100)));
    }

    public function store(Request $request, BusinessChannelService $service): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'], 'slug' => ['nullable','string','max:191'],
            'description' => ['nullable','string','max:2000'], 'category' => ['nullable','string','max:120'],
            'visibility' => ['required','in:public,private'], 'posting_policy' => ['required','in:members,moderators,owners'],
            'is_announcement' => ['boolean'], 'linked_entity_type' => ['nullable','string','max:120'],
            'linked_entity_id' => ['nullable','string','max:191'], 'user_ids' => ['array'], 'user_ids.*' => ['integer','distinct','exists:users,id'],
            'device_id' => ['nullable','string','max:191'],
        ]);
        $channel = $service->create($this->tenantId($request), (int) $request->user()->getAuthIdentifier(), $data, $data['device_id'] ?? null);
        return response()->json($channel, 201);
    }

    public function update(Request $request, TeamConversation $conversation, TeamConversationPolicy $policy, BusinessChannelService $service): JsonResponse
    {
        $this->authorizeAccess($request, $conversation, false);
        $actorId = (int) $request->user()->getAuthIdentifier();
        abort_unless($policy->canManage($conversation, $actorId), 403);
        $data = $request->validate([
            'name' => ['sometimes','required','string','max:255'], 'slug' => ['sometimes','required','string','max:191'],
            'description' => ['sometimes','nullable','string','max:2000'], 'category' => ['sometimes','nullable','string','max:120'],
            'visibility' => ['sometimes','in:public,private'], 'posting_policy' => ['sometimes','in:members,moderators,owners'],
            'is_announcement' => ['sometimes','boolean'], 'linked_entity_type' => ['sometimes','nullable','string','max:120'],
            'linked_entity_id' => ['sometimes','nullable','string','max:191'],
        ]);
        return response()->json($service->update($conversation, $actorId, $data));
    }

    public function join(Request $request, TeamConversation $conversation, BusinessChannelService $service): JsonResponse
    {
        abort_unless($conversation->tenant_id === $this->tenantId($request), 404);
        return response()->json($service->joinPublic($conversation, (int) $request->user()->getAuthIdentifier()), 201);
    }

    public function archive(Request $request, TeamConversation $conversation, TeamConversationPolicy $policy, BusinessChannelService $service): JsonResponse
    {
        $this->authorizeAccess($request, $conversation, false);
        $actorId = (int) $request->user()->getAuthIdentifier();
        abort_unless($policy->canManage($conversation, $actorId), 403);
        $data = $request->validate(['archived' => ['required','boolean']]);
        return response()->json($service->archive($conversation, $actorId, (bool) $data['archived']));
    }

    private function authorizeAccess(Request $request, TeamConversation $conversation, bool $requireMembership = true): void
    {
        abort_unless($conversation->tenant_id === $this->tenantId($request) && $conversation->isChannel(), 404);
        if ($requireMembership) abort_unless($conversation->hasParticipant((int) $request->user()->getAuthIdentifier()), 403);
    }

    private function tenantId(Request $request): int
    {
        $u=$request->user(); return (int) ($u->tenant_id ?? $u->company_id ?? $u->getAuthIdentifier());
    }
}
