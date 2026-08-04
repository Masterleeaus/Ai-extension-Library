<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers\Api\TitanAI;

use App\Extensions\Chatbot\System\TitanAI\Runtime\Tools\FieldServiceToolRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FieldServiceToolController extends Controller
{
    public function __construct(private readonly FieldServiceToolRegistry $tools) {}

    public function index(): JsonResponse
    {
        $items = array_values($this->tools->all());
        return response()->json(['data' => $items, 'count' => count($items)]);
    }

    public function match(Request $request): JsonResponse
    {
        $validated = $request->validate(['message' => ['required','string','max:8000']]);
        return response()->json(['data' => $this->tools->match($validated['message'])]);
    }
}
