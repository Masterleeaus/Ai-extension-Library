<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\TeamChat;

use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversation;
use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversationParticipant;
use App\Extensions\Chatbot\System\Services\TeamChat\TeamMembershipService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TeamMembershipController extends Controller
{
    public function store(Request $request, TeamConversation $conversation, TeamMembershipService $service): JsonResponse
    {
        $this->authorizeTenant($request, $conversation);
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id'], 'role' => ['nullable', 'in:admin,moderator,member']]);
        $participant = $service->add($conversation, (int) $request->user()->getAuthIdentifier(), (int) $data['user_id'], $data['role'] ?? 'member');
        return response()->json($participant, 201);
    }

    public function update(Request $request, TeamConversation $conversation, TeamConversationParticipant $participant, TeamMembershipService $service): JsonResponse
    {
        $this->authorizeTenant($request, $conversation);
        $data = $request->validate(['role' => ['required', 'in:admin,moderator,member']]);
        return response()->json($service->changeRole($conversation, (int) $request->user()->getAuthIdentifier(), $participant, $data['role']));
    }

    public function destroy(Request $request, TeamConversation $conversation, TeamConversationParticipant $participant, TeamMembershipService $service): JsonResponse
    {
        $this->authorizeTenant($request, $conversation);
        $service->remove($conversation, (int) $request->user()->getAuthIdentifier(), $participant);
        return response()->json(['status' => 'success']);
    }

    private function authorizeTenant(Request $request, TeamConversation $conversation): void
    {
        $user = $request->user();
        $tenantId = (int) ($user->tenant_id ?? $user->company_id ?? $user->getAuthIdentifier());
        abort_unless($conversation->tenant_id === $tenantId, 404);
    }
}
