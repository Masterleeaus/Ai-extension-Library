<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Services\TeamChat;

use App\Extensions\Chatbot\System\Events\TeamChat\TeamConversationUpdated;
use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversation;
use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversationParticipant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BusinessChannelService
{
    public function create(int $tenantId, int $actorId, array $data, ?string $deviceId = null): TeamConversation
    {
        $slug = Str::slug((string) ($data['slug'] ?? $data['name']));
        if ($slug === '') {
            throw ValidationException::withMessages(['slug' => 'A valid channel slug is required.']);
        }

        $exists = TeamConversation::query()->where('tenant_id', $tenantId)->where('slug', $slug)->exists();
        if ($exists) {
            throw ValidationException::withMessages(['slug' => 'This channel slug is already in use.']);
        }

        return DB::transaction(function () use ($tenantId, $actorId, $data, $deviceId, $slug): TeamConversation {
            $conversation = TeamConversation::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'name' => trim((string) $data['name']),
                'slug' => $slug,
                'type' => 'channel',
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'owner_id' => $actorId,
                'visibility' => $data['visibility'] ?? 'private',
                'posting_policy' => $data['posting_policy'] ?? 'members',
                'is_announcement' => (bool) ($data['is_announcement'] ?? false),
                'linked_entity_type' => $data['linked_entity_type'] ?? null,
                'linked_entity_id' => isset($data['linked_entity_id']) ? (string) $data['linked_entity_id'] : null,
                'originating_device_id' => $deviceId,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            TeamConversationParticipant::query()->create([
                'conversation_id' => $conversation->id,
                'tenant_id' => $tenantId,
                'user_id' => $actorId,
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            foreach (array_unique(array_map('intval', $data['user_ids'] ?? [])) as $userId) {
                if ($userId === $actorId) continue;
                TeamConversationParticipant::query()->firstOrCreate(
                    ['conversation_id' => $conversation->id, 'user_id' => $userId],
                    ['tenant_id' => $tenantId, 'role' => 'member', 'joined_at' => now()]
                );
            }

            event(new TeamConversationUpdated($conversation, 'channel-created', $actorId));
            return $conversation->load('participants');
        });
    }

    public function update(TeamConversation $conversation, int $actorId, array $data): TeamConversation
    {
        if (! $conversation->isChannel()) {
            throw ValidationException::withMessages(['conversation' => 'Only channels can use channel settings.']);
        }
        if (array_key_exists('slug', $data)) {
            $slug = Str::slug((string) $data['slug']);
            $duplicate = TeamConversation::query()->where('tenant_id', $conversation->tenant_id)
                ->where('slug', $slug)->whereKeyNot($conversation->getKey())->exists();
            if ($duplicate) throw ValidationException::withMessages(['slug' => 'This channel slug is already in use.']);
            $data['slug'] = $slug;
        }
        $conversation->fill($data);
        $conversation->updated_by = $actorId;
        $conversation->version = ((int) $conversation->version) + 1;
        $conversation->save();
        event(new TeamConversationUpdated($conversation, 'channel-updated', $actorId));
        return $conversation->fresh(['participants']);
    }

    public function joinPublic(TeamConversation $conversation, int $userId): TeamConversationParticipant
    {
        abort_unless($conversation->isChannel() && $conversation->visibility === 'public' && ! $conversation->channel_archived_at, 403);
        $participant = TeamConversationParticipant::query()->withTrashed()->firstOrNew([
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
        ]);
        $participant->fill(['tenant_id' => $conversation->tenant_id, 'role' => 'member', 'joined_at' => now(), 'left_at' => null, 'archived_at' => null]);
        $participant->save();
        event(new TeamConversationUpdated($conversation, 'channel-joined', $userId));
        return $participant;
    }

    public function archive(TeamConversation $conversation, int $actorId, bool $archived): TeamConversation
    {
        $conversation->update([
            'channel_archived_at' => $archived ? now() : null,
            'updated_by' => $actorId,
            'version' => ((int) $conversation->version) + 1,
        ]);
        event(new TeamConversationUpdated($conversation, $archived ? 'channel-archived' : 'channel-restored', $actorId));
        return $conversation->fresh();
    }
}
