<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Controllers;

use App\Extensions\AiChatProImageChat\System\Models\AiChatProImageModel;
use App\Extensions\AiChatProImageChat\System\Services\AIChatImageService;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use App\Models\OpenaiGeneratorChatCategory;
use App\Models\UserOpenai;
use App\Models\UserOpenaiChat;
use App\Models\UserOpenaiChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EditorController extends Controller
{
    /**
     * Display the AI Photoshoot image editor (variations) page.
     */
    public function index(Request $request): View
    {
        $activeImageModels = AIChatImageService::getActiveImageModels();
        $chat = null;

        $category = OpenaiGeneratorChatCategory::query()
            ->whereNotIn('slug', ['ai_vision', 'ai_webchat', 'ai_pdf'])
            ->where('role', 'default')
            ->first();

        if ($category) {
            $chat = new UserOpenaiChat;
            $chat->user_id = Auth::id();
            $chat->chat_type = 'chatpro-image';
            $chat->openai_chat_category_id = $category?->id;
            $chat->title = __('AI Photoshoot Editor Session');
            $chat->total_credits = 0;
            $chat->total_words = 0;
            $chat->save();

            $message = new UserOpenaiChatMessage;
            $message->user_openai_chat_id = $chat->id;
            $message->user_id = Auth::id();
            $message->response = 'First Initiation';
            $message->output = __('AI Photoshoot editor session started.');
            $message->hash = Str::random(256);
            $message->credits = 0;
            $message->words = 0;
            $message->save();
        }

        return view('ai-photoshoot::editor', [
            'activeImageModels' => $activeImageModels,
            'chat'              => $chat,
            'app_is_demo'       => Helper::appIsDemo(),
        ]);
    }

    /**
     * Paginated history of completed AI Photoshoot images
     * (photoshoots + edit-image outputs) for the editor's history rail.
     *
     * Returns the same shape AI Image Pro's completed-images endpoint
     * returns so the front-end can stay identical.
     */
    public function completedImages(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 50);
        $page = max((int) $request->get('page', 1), 1);

        $query = UserOpenai::query()
            ->where('user_id', Auth::id())
            ->where('is_ai_photo_studio', true)
            ->whereNotNull('output')
            ->where('output', '!=', '')
            ->where('response', '!=', 'APS-VIDEO')
            ->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $images = $paginator->getCollection()->map(function (UserOpenai $record): array {
            $output = (string) $record->output;
            $extension = strtolower(pathinfo($output, PATHINFO_EXTENSION));
            $isVideo = in_array($extension, ['mp4', 'mov', 'avi', 'webm', 'mkv'], true);

            if ($isVideo) {
                return [];
            }

            return [
                'id'               => $record->id,
                'prompt'           => (string) ($record->input ?? ''),
                'generated_images' => [$output],
                'thumbnails'       => [ThumbImage($output)],
            ];
        })->filter()->values()->toArray();

        return response()->json([
            'images'   => $images,
            'has_more' => $paginator->hasMorePages(),
            'page'     => $paginator->currentPage(),
            'total'    => $paginator->total(),
        ]);
    }

    /**
     * Delete the AiChatProImageModel tracking record once polling has
     * completed, so the edit doesn't double-up in the AI Chat Pro
     * Image Chat list.
     */
    public function deleteEditRecord(Request $request): JsonResponse
    {
        $request->validate([
            'record_id' => 'required|integer',
        ]);

        $record = AiChatProImageModel::query()
            ->where('id', $request->integer('record_id'))
            ->where('user_id', Auth::id())
            ->first();

        if ($record) {
            $record->delete();
        }

        return response()->json(['success' => true]);
    }
}
