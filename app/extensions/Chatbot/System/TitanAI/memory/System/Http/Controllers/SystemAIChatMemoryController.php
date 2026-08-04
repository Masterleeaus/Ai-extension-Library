<?php

namespace App\Extensions\SystemAIChatMemory\System\Http\Controllers;

use App\Extensions\SystemAIChatMemory\System\Models\UserChatInstruction;
use App\Http\Controllers\Controller;
use App\Models\UserOpenaiChat;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SystemAIChatMemoryController extends Controller
{
    /**
     * Get instructions for current user/guest
     */
    public function getInstructions(Request $request): JsonResponse
    {
        $request->validate([
            'chat_id' => 'required|integer',
        ]);

        $chat = UserOpenaiChat::query()->with(['category:id,instructions'])->select(['id','user_id','openai_chat_category_id'])->find($chatId);

        if (! $chat) {
            return response()->json([
                'success' => false,
                'message' => __('Chat not found.'),
            ], 404);
        }

        // Check access for authenticated users
        if (Auth::check() && $chat->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('Access denied.'),
            ], 403);
        }

        if (! $chat->category) {
            return response()->json(['success' => false, 'message' => __('Chat category not found.')], 404);
        }

        $categoryId = $chat->category->id;
        $adminInstructions = $chat->category->instructions; // Admin's default instructions

        if (Auth::check()) {
            // Get user-specific instructions
            $userInstructions = UserChatInstruction::getForUser(Auth::id(), $categoryId);
        } else {
            // Get guest instructions by IP
            $ipAddress = (string) $request->ip();
            $userInstructions = UserChatInstruction::getForGuest($ipAddress, $categoryId);
        }

        return response()->json([
            'success'            => true,
            'admin_instructions' => $adminInstructions ?? '',
            'user_instructions'  => $userInstructions ?? '',
            'has_user_override'  => ! empty($userInstructions),
            'type'               => Auth::check() ? 'user' : 'guest',
        ]);
    }

    /**
     * Save instructions for current user/guest
     */
    public function saveInstructions(Request $request): JsonResponse
    {
        $request->validate([
            'instructions' => 'nullable|string|max:' . (int) config('system-ai-chat-memory.max_instruction_characters', 5000),
            'chat_id'      => 'required|integer',
        ]);

        $chatId = (int) $request->input('chat_id');
        if (! Auth::check() && ! (bool) config('system-ai-chat-memory.allow_guest_memory', false)) {
            return response()->json([
                'success' => false,
                'message' => __('Guest memory is disabled.'),
            ], 403);
        }

        $chatId = $request->input('chat_id');
        $chat = UserOpenaiChat::query()->with(['category:id,instructions'])->select(['id','user_id','openai_chat_category_id'])->find($chatId);

        if (! $chat) {
            return response()->json([
                'success' => false,
                'message' => __('Chat not found.'),
            ], 404);
        }

        // Check access for authenticated users
        if (Auth::check() && $chat->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('Access denied.'),
            ], 403);
        }

        if (! $chat->category) {
            return response()->json(['success' => false, 'message' => __('Chat category not found.')], 404);
        }

        $categoryId = $chat->category->id;
        $instructions = $request->input('instructions');

        try {
            if (Auth::check()) {
                // Save for authenticated user
                UserChatInstruction::setForUser(Auth::id(), $categoryId, $instructions);
            } else {
                // Save for guest by IP
                $ipAddress = (string) $request->ip();
                UserChatInstruction::setForGuest($ipAddress, $categoryId, $instructions);
            }

            Cache::forget('system-ai-chat-memory:instructions:' . (Auth::id() ?: $request->ip()) . ':' . $categoryId);
            $this->auditOperation('memory.save', ['chat_id' => $chatId]);

            return response()->json([
                'success' => true,
                'message' => __('Your personal instructions have been saved successfully.'),
                'type'    => Auth::check() ? 'user' : 'guest',
            ]);
        } catch (Exception $e) {
            Log::error('Error saving user instructions: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to save instructions. Please try again.'),
            ], 500);
        }
    }

    /**
     * Clear user instructions (revert to admin defaults)
     */
    public function clearInstructions(Request $request): JsonResponse
    {
        $request->validate([
            'chat_id' => 'required|integer',
        ]);

        if (! Auth::check() && ! (bool) config('system-ai-chat-memory.allow_guest_memory', false)) {
            return response()->json([
                'success' => false,
                'message' => __('Guest memory is disabled.'),
            ], 403);
        }

        $chatId = $request->input('chat_id');
        $chat = UserOpenaiChat::query()->with(['category:id,instructions'])->select(['id','user_id','openai_chat_category_id'])->find($chatId);

        if (! $chat) {
            return response()->json([
                'success' => false,
                'message' => __('Chat not found.'),
            ], 404);
        }

        // Check access for authenticated users
        if (Auth::check() && $chat->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('Access denied.'),
            ], 403);
        }

        if (! $chat->category) {
            return response()->json(['success' => false, 'message' => __('Chat category not found.')], 404);
        }

        $categoryId = $chat->category->id;

        try {
            if (Auth::check()) {
                // Delete user-specific instruction
                UserChatInstruction::where('user_id', Auth::id())
                    ->where('openai_chat_category_id', $categoryId)
                    ->delete();
            } else {
                // Delete guest instruction
                $ipAddress = (string) $request->ip();
                UserChatInstruction::whereNull('user_id')
                    ->where('ip_address', $ipAddress)
                    ->where('openai_chat_category_id', $categoryId)
                    ->delete();
            }

            Cache::forget('system-ai-chat-memory:instructions:' . (Auth::id() ?: $request->ip()) . ':' . $categoryId);
            $this->auditOperation('memory.clear', ['chat_id' => $chatId]);

            return response()->json([
                'success' => true,
                'message' => __('Your personal instructions have been cleared. Admin defaults will be used.'),
            ]);
        } catch (Exception $e) {
            Log::error('Error clearing user instructions: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to clear instructions. Please try again.'),
            ], 500);
        }
    }

    private function auditOperation(string $operation, array $context = []): void
    {
        Log::info('SystemAIChat extension operation.', array_merge([
            'operation' => $operation,
            'user_id' => auth()->id(),
            'extension' => 'memory_mutation',
        ], $context));
    }
}
