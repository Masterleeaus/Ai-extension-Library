<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers\Api;

use App\Extensions\Chatbot\System\Titan\TitanRegistry;
use App\Extensions\Chatbot\System\TitanAI\WorkCoreApps\WorkCoreAppBridge;
use App\Extensions\Chatbot\System\TitanShell\PlatformApplicationRegistry;
use App\Extensions\Chatbot\System\TitanShell\TemplateSchema;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TitanController extends Controller
{
    public function index(WorkCoreAppBridge $workcore): JsonResponse
    {
        $applications = $this->applications($workcore);

        return response()->json([
            'applications' => $applications,
            // Compatibility alias for clients that used the old `templates` key
            // as the top-level app list.
            'templates' => $applications,
            'vertical_templates' => TitanRegistry::toArray(),
            'legacy_slug_map' => PlatformApplicationRegistry::legacyMap(),
            'workcore_apps' => $this->workCoreApplications($applications),
            'legacy_workcore_apps' => $workcore->apps(),
            'workcore_runtime_available' => $workcore->runtimeAvailable(),
        ]);
    }

    public function show(string $app, WorkCoreAppBridge $workcore): JsonResponse
    {
        $canonical = PlatformApplicationRegistry::canonicalSlug($app);
        if ($canonical !== null) {
            return response()->json($this->application($canonical, $workcore, $app));
        }

        $template = TitanRegistry::get($app);

        return $template
            ? response()->json(['kind' => 'vertical-template', ...$template])
            : response()->json(['message' => 'Titan application or vertical template not found.'], 404);
    }

    public function install(Request $request, string $app): JsonResponse
    {
        $canonical = PlatformApplicationRegistry::canonicalSlug($app);
        if ($canonical !== null) {
            $application = PlatformApplicationRegistry::get($canonical);

            return response()->json([
                'installed' => true,
                'kind' => 'platform-application',
                'application' => $canonical,
                'requested_slug' => $app,
                'name' => $request->string('name')->toString() ?: $application['name'],
                'schema' => TemplateSchema::resolve($canonical),
                'config' => $request->input('config', []),
            ]);
        }

        $template = TitanRegistry::get($app);
        if (! $template) {
            return response()->json(['message' => 'Titan application or vertical template not found.'], 404);
        }

        return response()->json([
            'installed' => true,
            'kind' => 'vertical-template',
            'template' => $app,
            'name' => $request->string('name')->toString() ?: $template['name'],
            'chatbot' => $template['chatbot'] ?? [],
            'config' => $request->input('config', []),
        ]);
    }

    public function manifest(string $app): JsonResponse
    {
        $canonical = PlatformApplicationRegistry::canonicalSlug($app);
        if ($canonical !== null) {
            $application = PlatformApplicationRegistry::get($canonical);

            return response()->json([
                'name' => $application['name'],
                'short_name' => str_replace('Titan ', '', $application['name']),
                'description' => $application['purpose'],
                'start_url' => route('api.v2.titan.install', ['app' => $canonical]),
                'scope' => '/titan/' . $canonical,
                'display' => 'standalone',
                'orientation' => 'portrait-primary',
                'background_color' => '#ffffff',
                'theme_color' => '#00d4ff',
                'icons' => [
                    ['src' => '/images/' . $canonical . '-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                    ['src' => '/images/' . $canonical . '-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ],
            ]);
        }

        $titanTemplate = TitanRegistry::create($app);

        return $titanTemplate
            ? response()->json($titanTemplate->manifest())
            : response()->json(['message' => 'Titan application or vertical template not found.'], 404);
    }

    /** @return list<array<string, mixed>> */
    private function applications(WorkCoreAppBridge $workcore): array
    {
        return array_map(
            fn (string $slug): array => $this->application($slug, $workcore),
            PlatformApplicationRegistry::slugs(),
        );
    }

    /** @return array<string, mixed> */
    private function application(string $slug, WorkCoreAppBridge $workcore, ?string $requestedSlug = null): array
    {
        $definition = PlatformApplicationRegistry::get($slug);

        return [
            'kind' => 'platform-application',
            ...$definition,
            'requested_slug' => $requestedSlug ?? $slug,
            'schema' => TemplateSchema::resolve($slug),
            'workcore' => $workcore->app($slug),
        ];
    }

    /** @param list<array<string, mixed>> $applications */
    private function workCoreApplications(array $applications): array
    {
        $mapped = [];

        foreach ($applications as $application) {
            $mapped[$application['slug']] = $application['workcore'];
        }

        return $mapped;
    }
}
