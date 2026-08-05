<?php

declare(strict_types=1);


namespace App\Extensions\Chatbot\System\Http\Controllers;

use App\Extensions\Chatbot\System\Models\ChatbotBuilder;
use App\Extensions\Chatbot\System\Services\Builder\ChatbotBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotBuilderController
{
    protected ChatbotBuilderService $builderService;

    public function __construct(ChatbotBuilderService $builderService)
    {
        $this->builderService = $builderService;
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'chatbot_id' => 'nullable|integer',
        ]);

        $tenantId = auth()->user()->tenant_id ?? $request->get('tenant_id');

        if (!$tenantId) {
            return response()->json(['error' => 'Tenant context required'], 403);
        }

        $builder = $this->builderService->createBuilder($tenantId, $request->all());

        return response()->json([
            'success' => true,
            'builder' => $builder,
            'current_step' => $builder->step_current,
        ]);
    }

    public function get(Request $request, int $builderId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $builder = $this->builderService->getBuilder($tenantId, $builderId);

        if (!$builder) {
            return response()->json(['error' => 'Builder not found'], 404);
        }

        return response()->json([
            'success' => true,
            'builder' => $builder,
            'is_complete' => $this->builderService->isBuilderComplete($builder),
        ]);
    }

    public function updateStep(Request $request, int $builderId): JsonResponse
    {
        $request->validate([
            'step' => 'required|string|in:configure,customize,train,embed,channel',
        ]);

        $tenantId = auth()->user()->tenant_id;

        try {
            $builder = ChatbotBuilder::where('tenant_id', $tenantId)
                ->where('id', $builderId)
                ->firstOrFail();

            $updatedBuilder = $this->builderService->updateStep(
                $builderId,
                $request->get('step'),
                $request->all()
            );

            return response()->json([
                'success' => true,
                'builder' => $updatedBuilder,
                'step_data' => $updatedBuilder->{$request->get('step')} ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function preview(Request $request, int $builderId): JsonResponse
    {
        $request->validate([
            'device_type' => 'nullable|string|in:mobile,tablet,desktop',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $builder = ChatbotBuilder::where('tenant_id', $tenantId)
            ->where('id', $builderId)
            ->firstOrFail();

        try {
            $preview = $this->builderService->generatePreview(
                $builderId,
                $request->get('device_type', 'mobile')
            );

            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function publish(Request $request, int $builderId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        try {
            $builder = ChatbotBuilder::where('tenant_id', $tenantId)
                ->where('id', $builderId)
                ->firstOrFail();

            $published = $this->builderService->publishBuilder($builderId);

            return response()->json([
                'success' => true,
                'message' => 'Builder published successfully',
                'builder' => $published,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getMobilekitComponent(Request $request): JsonResponse
    {
        $request->validate([
            'component_type' => 'required|string',
        ]);

        try {
            $component = $this->builderService->getMobilekitComponent(
                $request->get('component_type')
            );

            return response()->json([
                'success' => true,
                'component' => $component,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function duplicate(Request $request, int $builderId): JsonResponse
    {
        $request->validate([
            'new_name' => 'required|string|max:255',
        ]);

        $tenantId = auth()->user()->tenant_id;

        try {
            ChatbotBuilder::where('tenant_id', $tenantId)
                ->where('id', $builderId)
                ->firstOrFail();

            $duplicated = $this->builderService->duplicateBuilder(
                $builderId,
                $request->get('new_name')
            );

            return response()->json([
                'success' => true,
                'builder' => $duplicated,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function fromTitanTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'template_slug' => 'required|string',
        ]);

        $tenantId = auth()->user()->tenant_id;

        try {
            $builder = $this->builderService->createFromTitanTemplate(
                $tenantId,
                $request->get('template_slug')
            );

            return response()->json([
                'success' => true,
                'builder' => $builder,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getTitanTemplates(Request $request): JsonResponse
    {
        try {
            $templates = \App\Extensions\Chatbot\System\Titan\TitanRegistry::all();

            return response()->json([
                'success' => true,
                'templates' => $templates->toArray(),
                'count' => $templates->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
