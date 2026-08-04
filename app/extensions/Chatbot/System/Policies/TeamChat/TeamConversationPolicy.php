<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Policies\TeamChat;

use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversation;
use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversationParticipant;

final class TeamConversationPolicy
{
    public function canManage(TeamConversation $conversation, int $userId): bool
    {
        return $conversation->participants()
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function canModerate(TeamConversation $conversation, int $userId): bool
    {
        return $conversation->participants()
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->whereIn('role', ['owner', 'admin', 'moderator'])
            ->exists();
    }


    public function canPost(TeamConversation $conversation, int $userId): bool
    {
        $participant = $conversation->participants()
            ->where('user_id', $userId)->whereNull('left_at')->first();

        if (! $participant || $conversation->channel_archived_at !== null) {
            return false;
        }

        if (! $conversation->isChannel() || $conversation->posting_policy === 'members') {
            return true;
        }

        if ($conversation->posting_policy === 'moderators') {
            return in_array($participant->role, ['owner', 'admin', 'moderator'], true);
        }

        return $participant->role === 'owner';
    }

    public function canRemove(TeamConversation $conversation, int $actorId, TeamConversationParticipant $target): bool
    {
        $actor = $conversation->participants()->where('user_id', $actorId)->whereNull('left_at')->first();
        if (! $actor || $target->conversation_id !== $conversation->id || $target->left_at !== null) {
            return false;
        }

        if ($target->role === 'owner') {
            return false;
        }

        if ($actor->role === 'owner') {
            return true;
        }

        return $actor->role === 'admin' && ! in_array($target->role, ['owner', 'admin'], true);
    }
}
