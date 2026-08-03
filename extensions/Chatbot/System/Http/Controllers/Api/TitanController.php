<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers\Api;

use App\Extensions\Chatbot\System\Titan\TitanRegistry;
use App\Extensions\Chatbot\System\TitanAI\WorkCoreApps\WorkCoreAppBridge;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TitanController extends Controller
{
    public function index(WorkCoreAppBridge $workcore): JsonResponse
    {
        return response()->json(['templates' => TitanRegistry::toArray(), 'workcore_apps' => $workcore->apps(), 'workcore_runtime_available' => $workcore->runtimeAvailable()]);
    }

    public function show(string $app, WorkCoreAppBridge $workcore): JsonResponse
    {
        $template = TitanRegistry::get($app);

        return $template
            ? response()->json([...$template, 'workcore' => $workcore->app($app)])
            : response()->json(['message' => 'Titan app template not found.'], 404);
    }

    public function install(Request $request, string $app): JsonResponse
    {
        $template = TitanRegistry::get($app);
        if (! $template) {
            return response()->json(['message' => 'Titan app template not found.'], 404);
        }

        return response()->json([
            'installed' => true,
            'template' => $app,
            'name' => $request->string('name')->toString() ?: $template['name'],
            'chatbot' => $template['chatbot'] ?? [],
            'config' => $request->input('config', []),
        ]);
    }

    public function manifest(string $app): JsonResponse
    {
        $titanApp = TitanRegistry::create($app);

        return $titanApp
            ? response()->json($titanApp->manifest())
            : response()->json(['message' => 'Titan app template not found.'], 404);
    }
}
