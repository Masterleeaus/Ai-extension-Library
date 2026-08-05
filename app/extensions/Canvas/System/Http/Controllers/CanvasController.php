<?php

namespace App\Extensions\Canvas\System\Http\Controllers;

use App\Concerns\HasErrorResponse;
use App\Http\Controllers\Controller;
use App\Models\UserOpenaiChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CanvasController extends Controller
{
    use HasErrorResponse;

    // store the content
    public function storeContent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content'    => 'required|nullable|string',
            'type'       => 'required|string|in:input,output',
            'message_id' => 'required|integer',
        ]);

        $message = $this->ownedMessage($validated['message_id']);

        try {
            if ($validated['type'] === 'input') {
                $message->tiptapContent()->updateOrCreate([], [
                    'input'   => $validated['content'],
                    'user_id' => auth()->id(),
                ]);
            } else {
                $message->tiptapContent()->updateOrCreate([], [
                    'output'  => $validated['content'],
                    'user_id' => auth()->id(),
                ]);
            }

            return response()->json(['status' => 'success']);
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while store tiptap content');
        }
    }

    // save the title
    public function saveTitle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message_id' => 'required|integer',
            'title'      => 'sometimes|nullable|string',
        ]);

        $message = $this->ownedMessage($validated['message_id']);

        try {
            $message->tiptapContent()->updateOrCreate([], [
                'title'   => $validated['title'] ?? null,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'success',
            ]);
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while save canvas title');
        }
    }

    private function ownedMessage(int|string $id): UserOpenaiChatMessage
    {
        return UserOpenaiChatMessage::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);
    }
}
