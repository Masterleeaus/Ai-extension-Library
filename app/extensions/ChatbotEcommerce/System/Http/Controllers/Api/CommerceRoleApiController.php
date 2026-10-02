<?php

declare(strict_types=1);

namespace App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\ChatbotEcommerce\System\Services\CommerceRoleRuntime;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CommerceRoleApiController extends Controller
{
    public function definitions(CommerceRoleRuntime $roles): JsonResponse
    {
        return response()->json(['data' => $roles->definitions()]);
    }

    public function resolve(Chatbot $chatbot, Request $request, CommerceRoleRuntime $roles): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['sometimes', 'string', 'max:60'],
            'intent' => ['sometimes', 'nullable', 'string', 'max:100'],
            'inbound_support' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $isSeller = $user !== null
            && (int) $user->getAuthIdentifier() === (int) $chatbot->getAttribute('user_id');

        $context = array_merge($validated, [
            'actor_type' => $isSeller ? 'seller' : 'customer',
            'authenticated' => $isSeller,
        ]);

        return response()->json(['data' => ['role' => $roles->resolve($context)]]);
    }
}
