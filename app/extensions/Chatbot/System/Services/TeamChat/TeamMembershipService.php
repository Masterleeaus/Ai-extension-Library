<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Services\TeamChat;

use App\Extensions\Chatbot\System\Events\TeamChat\TeamConversationUpdated;
use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversation;
use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversationParticipant;
use App\Extensions\Chatbot\System\Policies\TeamChat\TeamConversationPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TeamMembershipService
{
    public function __construct(private readonly TeamConversationPolicy $policy)
    {
    }

    public function add(TeamConversation $conversation, int $actorId, int $userId, string $role = 'member'): TeamConversationParticipant
    {
        abort_unless($conversation->type === 'group' && $this->policy->canManage($conversation, $actorId), 403);
        if (! in_array($role, ['admin', 'moderator', 'member'], true)) {
            throw ValidationException::withMessages(['role' => 'Unsupported participant role.']);
        }

        $participant = DB::transaction(function () use ($conversation, $userId, $role): TeamConversationParticipant {
            $participant = TeamConversationParticipant::withTrashed()->firstOrNew([
                'conversation_id' => $conversation->id,
                'tenant_id' => $conversation->tenant_id,
                'user_id' => $userId,
            ]);
            $participant->fill([
                'role' => $role,
                'joined_at' => now(),
                'left_at' => null,
                'archived_at' => null,
            ]);
            $participant->deleted_at = null;
            $participant->save();
            $conversation->increment('version');
            $conversation->touch();
            return $participant;
        });

        event(new TeamConversationUpdated($conversation->fresh(), 'participant-added', $actorId));
        return $participant;
    }

    public function remove(TeamConversation $conversation, int $actorId, TeamConversationParticipant $target): void
    {
        abort_unless($this->policy->canRemove($conversation, $actorId, $target), 403);
        DB::transaction(function () use ($conversation, $target): void {
            $target->update(['left_at' => now(), 'archived_at' => now()]);
            $conversation->increment('version');
            $conversation->touch();
        });
        event(new TeamConversationUpdated($conversation->fresh(), 'participant-removed', $actorId));
    }

    public function changeRole(TeamConversation $conversation, int $actorId, TeamConversationParticipant $target, string $role): TeamConversationParticipant
    {
        abort_unless($this->policy->canManage($conversation, $actorId), 403);
        abort_if($target->conversation_id !== $conversation->id || $target->role === 'owner', 403);
        if (! in_array($role, ['admin', 'moderator', 'member'], true)) {
            throw ValidationException::withMessages(['role' => 'Unsupported participant role.']);
        }
        $target->update(['role' => $role]);
        $conversation->increment('version');
        event(new TeamConversationUpdated($conversation->fresh(), 'participant-role-changed', $actorId));
        return $target->fresh();
    }
}
