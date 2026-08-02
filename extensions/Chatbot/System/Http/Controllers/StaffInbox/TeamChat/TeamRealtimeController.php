<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\TeamChat;

use App\Extensions\Chatbot\System\Events\TeamChat\TeamUserTyping;
use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversation;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TeamRealtimeController extends Controller
{
    public function typing(Request $request, TeamConversation $conversation): JsonResponse
    {
        $userId = (int) $request->user()->getAuthIdentifier();
        abort_unless($conversation->hasParticipant($userId), 403);
        $data = $request->validate(['typing' => ['required', 'boolean']]);
        event(new TeamUserTyping($conversation, $userId, (bool) $data['typing']));
        return response()->json(['status' => 'accepted']);
    }
}
