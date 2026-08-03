<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Http\Controllers;

use App\Extensions\SystemAIChatSkills\System\Http\Requests\ImportGithubSkillRequest;
use App\Extensions\SystemAIChatSkills\System\Http\Requests\StoreSkillRequest;
use App\Extensions\SystemAIChatSkills\System\Imports\SkillImportService;
use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use App\Extensions\SystemAIChatSkills\System\Services\SkillParserService;
use App\Extensions\SystemAIChatSkills\System\Services\SkillReleaseService;
use App\Extensions\SystemAIChatSkills\System\Support\SkillOwnershipType;
use App\Extensions\SystemAIChatSkills\System\Support\SkillVisibility;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class AdminSkillController extends Controller
{
    public function __construct(
        private SkillParserService $parser,
        private SkillReleaseService $releases,
        private SkillImportService $imports,
    ) {}

    public function index(): View
    {
        $skills = Skill::query()
            ->with('creator')
            ->latest()
            ->paginate(20);

        return view('system-ai-chat-skills::admin.index', compact('skills'));
    }

    public function store(StoreSkillRequest $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json(['message' => __('This feature is disabled in Demo version.')], 403);
        }

        if ($request->hasFile('file')) {
            try {
                $parsed = $this->parser->parseSkillFile($request->file('file'));
            } catch (\InvalidArgumentException|RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            if (! $this->parser->validateSchema($parsed)) {
                return response()->json(['message' => __('Invalid skill file. SKILL.md must contain a name and instructions.')], 422);
            }

            $parsed['instructions'] = $this->parser->sanitizeInstructions($parsed['instructions']);
            $slug = Skill::uniqueSlug($parsed['name']);

            try {
                $result = $this->imports->import([
                    'user_id'            => null,
                    'company_id'         => null,
                    'ownership_type'     => SkillOwnershipType::PLATFORM,
                    'visibility'         => SkillVisibility::PLATFORM_PUBLIC,
                    'created_by'         => $request->user()?->id,
                    'managed_by'         => $request->user()?->id,
                    'name'               => $parsed['name'],
                    'slug'               => $slug,
                    'description'        => $parsed['description'] ?: $parsed['name'],
                    'instructions'       => $parsed['instructions'],
                    'version'            => $parsed['version'] ?? '1.0.0',
                    'metadata'           => $parsed['metadata'] ?? [],
                    'source'             => 'uploaded',
                    'is_public'          => true,
                    'status'             => 'active',
                ], $parsed['files'] ?? [], null, $parsed['import_report'] ?? []);
            } catch (\InvalidArgumentException|RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'message' => __('Skill uploaded successfully.'),
                'skill'   => $result->skill,
                'import_report' => $result->report,
            ]);
        }

        // Manual creation
        $skill = Skill::query()->create([
            'user_id'      => null,
            'company_id'   => null,
            'ownership_type' => SkillOwnershipType::PLATFORM,
            'visibility'   => SkillVisibility::PLATFORM_PUBLIC,
            'created_by'   => $request->user()?->id,
            'managed_by'   => $request->user()?->id,
            'name'         => $request->input('name'),
            'slug'         => Skill::uniqueSlug($request->input('name')),
            'description'  => $request->input('description'),
            'instructions' => $this->parser->sanitizeInstructions($request->input('instructions')),
            'version'      => $request->input('version', '1.0.0'),
            'source'       => 'created',
            'is_public'    => true,
            'status'       => 'active',
        ]);

        return response()->json([
            'message' => __('Skill created successfully.'),
            'skill'   => $skill,
        ]);
    }

    public function importFromGithub(ImportGithubSkillRequest $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json(['message' => __('This feature is disabled in Demo version.')], 403);
        }

        try {
            $parsed = $this->parser->parseGithubUrl($request->input('url'));
        } catch (\InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! $this->parser->validateSchema($parsed)) {
            return response()->json(['message' => __('Invalid skill file. SKILL.md must contain a name and instructions.')], 422);
        }

        $parsed['instructions'] = $this->parser->sanitizeInstructions($parsed['instructions']);
        $slug = Skill::uniqueSlug($parsed['name']);

        try {
            $result = $this->imports->import([
                'user_id'            => null,
                'company_id'         => null,
                'ownership_type'     => SkillOwnershipType::PLATFORM,
                'visibility'         => SkillVisibility::PLATFORM_PUBLIC,
                'created_by'         => $request->user()?->id,
                'managed_by'         => $request->user()?->id,
                'name'               => $parsed['name'],
                'slug'               => $slug,
                'description'        => $parsed['description'] ?: $parsed['name'],
                'instructions'       => $parsed['instructions'],
                'version'            => $parsed['version'] ?? '1.0.0',
                'metadata'           => $parsed['metadata'] ?? [],
                'source'             => 'github',
                'source_url'         => $request->input('url'),
                'is_public'          => true,
                'status'             => 'active',
            ], $parsed['files'] ?? [], null, $parsed['import_report'] ?? []);
        } catch (\InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('Skill imported from GitHub successfully.'),
            'skill'   => $result->skill,
            'import_report' => $result->report,
        ]);
    }

    public function scanGithub(Request $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json(['message' => __('This feature is disabled in Demo version.')], 403);
        }

        $request->validate(['url' => ['required', 'url']]);

        try {
            $files = $this->parser->scanGithubRepo($request->input('url'));
        } catch (\InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (empty($files)) {
            return response()->json(['message' => __('No skills found in this repository. Skills must contain a SKILL.md file.')], 422);
        }

        return response()->json(['files' => $files]);
    }

    public function importGithubBulk(Request $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json(['message' => __('This feature is disabled in Demo version.')], 403);
        }

        $request->validate([
            'url'     => ['required', 'url'],
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'string'],
        ]);

        $url = $request->input('url');
        $skillFolders = $request->input('files');
        $imported = 0;
        $errors = [];

        foreach ($skillFolders as $skillFolder) {
            try {
                $folderPath = dirname($skillFolder);
                if ($folderPath === '.') {
                    $folderPath = $skillFolder;
                }

                $parsed = $this->parser->fetchGithubSkillFolder($url, $folderPath);

                if (! $this->parser->validateSchema($parsed)) {
                    $errors[] = $folderPath . ': ' . __('Missing name or instructions.');

                    continue;
                }

                $parsed['instructions'] = $this->parser->sanitizeInstructions($parsed['instructions']);
                $slug = Skill::uniqueSlug($parsed['name']);

                $this->imports->import([
                    'user_id'            => null,
                    'company_id'         => null,
                    'ownership_type'     => SkillOwnershipType::PLATFORM,
                    'visibility'         => SkillVisibility::PLATFORM_PUBLIC,
                    'created_by'         => $request->user()?->id,
                    'managed_by'         => $request->user()?->id,
                    'name'               => $parsed['name'],
                    'slug'               => $slug,
                    'description'        => $parsed['description'] ?: $parsed['name'],
                    'instructions'       => $parsed['instructions'],
                    'version'            => $parsed['version'] ?? '1.0.0',
                    'metadata'           => $parsed['metadata'] ?? [],
                    'source'             => 'github',
                    'source_url'         => $url,
                    'is_public'          => true,
                    'status'             => 'active',
                ], $parsed['files'] ?? [], null, $parsed['import_report'] ?? []);

                $imported++;
            } catch (\InvalidArgumentException|RuntimeException $e) {
                $errors[] = $skillFolder . ': ' . $e->getMessage();
            }
        }

        $message = __(':count skill(s) imported successfully.', ['count' => $imported]);
        if (! empty($errors)) {
            $message .= ' ' . __(':count file(s) skipped.', ['count' => count($errors)]);
        }

        return response()->json([
            'message'  => $message,
            'imported' => $imported,
            'errors'   => $errors,
        ]);
    }

    public function update(Request $request, Skill $skill): JsonResponse
    {
        $this->authorize('update', $skill);

        if (Helper::appIsDemo()) {
            return response()->json(['message' => __('This feature is disabled in Demo version.')], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:2000'],
            'instructions' => ['sometimes', 'string'],
            'version' => ['sometimes', 'string', 'max:64'],
            'release_notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        if (isset($validated['status'])) {
            $skill->update(['status' => $validated['status']]);
        }

        $releaseFields = array_intersect_key($validated, array_flip([
            'name', 'description', 'instructions', 'version', 'release_notes',
        ]));

        if (isset($releaseFields['instructions'])) {
            $releaseFields['instructions'] = $this->parser->sanitizeInstructions($releaseFields['instructions']);
        }

        if ($releaseFields === []) {
            return response()->json([
                'message' => __('Skill status updated successfully.'),
                'skill' => $skill->fresh(),
            ]);
        }

        try {
            $draft = $this->releases->createDraft(
                $skill,
                $request->user()?->id,
                null,
                $releaseFields['version'] ?? null,
            );
            unset($releaseFields['version']);
            $draft = $this->releases->updateDraft($draft, $releaseFields);
        } catch (InvalidArgumentException|LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('A new draft version was created. Publish it to change the active skill.'),
            'skill' => $skill->fresh(),
            'draft' => $draft,
        ]);
    }

    public function show(Skill $skill): JsonResponse
    {
        $this->authorize('view', $skill);

        return response()->json([
            'skill' => [
                'id'                => $skill->id,
                'name'              => $skill->name,
                'slug'              => $skill->slug,
                'description'       => $skill->description,
                'instructions'      => $skill->instructions,
                'bundled_resources' => $skill->bundled_resources,
                'source'            => $skill->source,
                'source_url'        => $skill->source_url,
                'is_public'         => $skill->is_public,
                'company_id'        => $skill->company_id,
                'ownership_type'    => $skill->ownership_type,
                'visibility'        => $skill->visibility,
                'created_by'        => $skill->created_by,
                'managed_by'        => $skill->managed_by,
                'version'           => $skill->version,
                'current_version_id' => $skill->current_version_id,
                'latest_published_version_id' => $skill->latest_published_version_id,
                'versions'          => $skill->versions()->orderByDesc('created_at')->get(),
                'created_at'        => $skill->created_at->toDateTimeString(),
            ],
        ]);
    }

    public function destroy(Skill $skill): JsonResponse
    {
        $this->authorize('delete', $skill);

        if (Helper::appIsDemo()) {
            return response()->json(['message' => __('This feature is disabled in Demo version.')], 403);
        }

        if ($skill->source === 'seeded') {
            return response()->json(['message' => __('Built-in skills cannot be deleted. You can deactivate them instead.')], 403);
        }

        $skill->users()->detach();
        $skill->companyInstallations()->delete();
        $skill->delete();

        return response()->json([
            'message' => __('Skill deleted successfully.'),
        ]);
    }
}
