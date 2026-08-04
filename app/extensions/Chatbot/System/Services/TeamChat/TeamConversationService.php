<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Services\TeamChat;

use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversation;
use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversationParticipant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class TeamConversationService
{
    public function create(int $tenantId, int $actorId, string $type, ?string $name, array $userIds, ?string $deviceId = null): TeamConversation
    {
        if (! in_array($type, ['direct', 'group'], true)) {
            throw ValidationException::withMessages(['type' => 'Unsupported conversation type.']);
        }

        $participantIds = array_values(array_unique(array_map('intval', [...$userIds, $actorId])));

        if ($type === 'direct' && count($participantIds) !== 2) {
            throw ValidationException::withMessages(['user_ids' => 'Direct conversations require exactly two participants.']);
        }

        if ($type === 'direct') {
            $existing = TeamConversation::query()
                ->where('tenant_id', $tenantId)
                ->where('type', 'direct')
                ->whereHas('participants', fn ($q) => $q->where('user_id', $participantIds[0])->whereNull('left_at'))
                ->whereHas('participants', fn ($q) => $q->where('user_id', $participantIds[1])->whereNull('left_at'))
                ->withCount(['participants as active_participants_count' => fn ($q) => $q->whereNull('left_at')])
                ->get()
                ->first(fn (TeamConversation $conversation) => $conversation->active_participants_count === 2);

            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($tenantId, $actorId, $type, $name, $participantIds, $deviceId): TeamConversation {
            $conversation = TeamConversation::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'name' => $type === 'group' ? trim((string) $name) : null,
                'type' => $type,
                'owner_id' => $actorId,
                'visibility' => 'private',
                'originating_device_id' => $deviceId,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            foreach ($participantIds as $userId) {
                TeamConversationParticipant::query()->create([
                    'conversation_id' => $conversation->id,
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'role' => $userId === $actorId ? 'owner' : 'member',
                    'joined_at' => now(),
                ]);
            }

            return $conversation->load('participants');
        });
    }

    public function leave(TeamConversation $conversation, int $actorId): void
    {
        $participant = $conversation->participants()->where('user_id', $actorId)->whereNull('left_at')->firstOrFail();

        DB::transaction(function () use ($conversation, $participant, $actorId): void {
            if ($participant->role === 'owner' && $conversation->type === 'group') {
                $successor = $conversation->participants()
                    ->where('user_id', '!=', $actorId)
                    ->whereNull('left_at')
                    ->orderByRaw("case role when 'admin' then 0 when 'moderator' then 1 else 2 end")
                    ->oldest('joined_at')
                    ->first();

                if (! $successor) {
                    throw ValidationException::withMessages(['conversation' => 'The final group owner cannot leave without another participant.']);
                }

                $successor->update(['role' => 'owner']);
                $conversation->update(['owner_id' => $successor->user_id, 'updated_by' => $actorId]);
            }

            $participant->update(['left_at' => now(), 'archived_at' => now()]);
            $conversation->increment('version');
            $conversation->touch();
        });
    }

    public function archiveForUser(TeamConversation $conversation, int $actorId, bool $archived): void
    {
        $conversation->participants()->where('user_id', $actorId)->whereNull('left_at')->firstOrFail()
            ->update(['archived_at' => $archived ? now() : null]);
    }
}
