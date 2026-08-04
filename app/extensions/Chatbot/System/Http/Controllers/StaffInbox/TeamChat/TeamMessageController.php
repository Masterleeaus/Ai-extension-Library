<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\TeamChat;

use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversation;
use App\Extensions\Chatbot\System\Models\TeamChat\TeamMessage;
use App\Extensions\Chatbot\System\Services\TeamChat\TeamMessageService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TeamMessageController extends Controller
{
    public function index(Request $request, TeamConversation $conversation): JsonResponse
    {
        $this->authorizeAccess($request, $conversation);

        $messages = $conversation->messages()->with('reads')->oldest('created_at')
            ->paginate(min(max((int) $request->integer('per_page', 50), 1), 100));

        return response()->json($messages);
    }

    public function store(Request $request, TeamConversation $conversation, TeamMessageService $service): JsonResponse
    {
        $this->authorizeAccess($request, $conversation);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
            'local_uuid' => ['nullable', 'uuid'],
            'device_id' => ['nullable', 'string', 'max:191'],
        ]);

        $message = $service->send(
            $conversation,
            $this->tenantId($request),
            (int) $request->user()->getAuthIdentifier(),
            $data['body'],
            $data['local_uuid'] ?? null,
            $data['device_id'] ?? null,
        );

        return response()->json($message, 201);
    }

    public function read(Request $request, TeamMessage $message, TeamMessageService $service): JsonResponse
    {
        $receipt = $service->markRead($message, $this->tenantId($request), (int) $request->user()->getAuthIdentifier());
        return response()->json($receipt);
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
