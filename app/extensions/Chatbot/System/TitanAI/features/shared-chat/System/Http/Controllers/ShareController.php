<?php

declare(strict_types=1);

namespace App\Extensions\SharedChat\System\Http\Controllers;

use App\Extensions\SharedChat\System\Models\ShareLink;
use App\Extensions\SharedChat\System\Services\ShareTokenService;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use App\Models\OpenaiGeneratorChatCategory;
use App\Models\UserOpenaiChat;
use App\Models\UserOpenaiChatMessage;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    public function share(Request $request, string $category, int $chat, int $message): View
    {
        $share = ShareLink::query()
            ->where('category', $category)->where('chat', $chat)->where('message', $message)
            ->where('time', (int) $request->query('time'))
            ->latest('id')->firstOrFail();

        abort_unless($share->isAvailable(), 404);

        if ($share->token_hash) {
            $token = (string) $request->query('token');
            abort_unless($token !== '' && hash_equals($share->token_hash, hash('sha256', $token)), 404);
        }

        $categoryModel = OpenaiGeneratorChatCategory::query()->whereSlug($category)->firstOrFail();
        $chatModel = UserOpenaiChat::query()->findOrFail($chat);
        abort_unless((int) $chatModel->openai_chat_category_id === (int) $categoryModel->id, 404);
        abort_unless($chatModel->messages()->whereKey($message)->exists(), 404);

        $share->forceFill([
            'access_count' => ((int) $share->access_count) + 1,
            'last_accessed_at' => now(),
        ])->save();

        $chatCompletions = str_replace(["\r", "\n"], '', (string) $categoryModel->chat_completions);
        if ($chatCompletions !== '') {
            $chatCompletions = json_decode($chatCompletions, true, 512, JSON_THROW_ON_ERROR);
        }

        return view('shared-chat::index', [
            'category' => $categoryModel,
            'chat' => $chatModel,
            'list' => $chatModel->toArray(),
            'chat_completions' => $chatCompletions,
            'lastThreeMessage' => $chatModel->messages()->whereNotNull('input')->latest()->take(2)->get()->reverse(),
            'messages' => $chatModel->messages()->where('id', '<=', $message)->orderBy('id')->get(),
        ]);
    }

    public function createLink(Request $request): JsonResponse
    {
        abort_unless((bool) config('shared-chat.enabled', true), 404);
        $data = $request->validate([
            'category_id' => ['required','integer'],
            'chat_id' => ['required','integer'],
            'expires_in_hours' => ['nullable','integer','min:1','max:' . config('shared-chat.maximum_expiry_hours', 720)],
        ]);

        $chat = UserOpenaiChat::query()->whereKey($data['chat_id'])->where('user_id', $request->user()->id)->firstOrFail();
        $category = OpenaiGeneratorChatCategory::query()->whereKey($data['category_id'])->firstOrFail();
        abort_unless((int) $chat->openai_chat_category_id === (int) $category->id, 422);
        $message = UserOpenaiChatMessage::query()->where('user_openai_chat_id', $chat->id)->latest('id')->firstOrFail();

        $issuedAt = now()->timestamp;
        $plainToken = bin2hex(random_bytes(32));
        $expiresAt = now()->addHours((int) ($data['expires_in_hours'] ?? config('shared-chat.default_expiry_hours', 168)));
        $url = Helper::parseUrl(config('app.url'), 'share', $category->slug, $chat->id, $message->id)
            . '?time=' . $issuedAt . '&token=' . $plainToken;

        $share = ShareLink::query()->create([
            'url' => $url,
            'category' => $category->slug,
            'chat' => $chat->id,
            'message' => $message->id,
            'time' => $issuedAt,
            'user_id' => $request->user()->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt,
        ]);

        return response()->json(['link' => $url, 'id' => $share->id, 'expires_at' => $expiresAt->toIso8601String()], 201);
    }

    public function revoke(Request $request, ShareLink $shareLink): JsonResponse
    {
        abort_unless((int) $shareLink->user_id === (int) $request->user()->id, 404);
        $shareLink->update(['revoked_at' => now()]);
        return response()->json(['revoked' => true]);
    }
}
