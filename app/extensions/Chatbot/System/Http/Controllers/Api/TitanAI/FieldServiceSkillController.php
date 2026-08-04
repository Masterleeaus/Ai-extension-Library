<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers\Api\TitanAI;

use App\Extensions\Chatbot\System\TitanAI\Runtime\Skills\FieldServiceSkillRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FieldServiceSkillController extends Controller
{
    public function __construct(private readonly FieldServiceSkillRegistry $skills) {}

    public function index(): JsonResponse
    {
        $items = array_map(static fn (array $skill): array => [
            'slug' => $skill['slug'], 'version' => $skill['version'],
            'description' => $skill['description'], 'capabilities' => $skill['capabilities'],
            'tools' => $skill['tools'], 'sha256' => $skill['sha256'],
        ], array_values($this->skills->all()));
        return response()->json(['data' => $items, 'count' => count($items)]);
    }

    public function match(Request $request): JsonResponse
    {
        $validated = $request->validate(['message' => ['required','string','max:8000'], 'template' => ['nullable','string','max:128']]);
        return response()->json(['data' => $this->skills->match($validated['message'], $validated['template'] ?? null)]);
    }
}
