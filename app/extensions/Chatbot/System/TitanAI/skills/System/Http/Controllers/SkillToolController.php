<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Http\Controllers;

use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolDefinition;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class SkillToolController extends Controller
{
    public function __construct(private readonly SkillToolRegistry $registry) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'tools' => array_map(
                fn (SkillToolDefinition $definition): array => $definition->toArray() + [
                    'executable' => $this->registry->canExecute($definition->key),
                ],
                $this->registry->all(),
            ),
        ]);
    }
}
