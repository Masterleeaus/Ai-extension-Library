<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Http\Controllers;

use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use App\Extensions\SystemAIChatSkills\System\Models\SkillVersion;
use App\Extensions\SystemAIChatSkills\System\Services\SkillParserService;
use App\Extensions\SystemAIChatSkills\System\Services\SkillReleaseService;
use App\Extensions\SystemAIChatSkills\System\Skills\SkillManifestValidator;
use App\Extensions\SystemAIChatSkills\System\Support\SkillVersionStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use LogicException;

final class SkillVersionController extends Controller
{
    public function __construct(
        private readonly SkillReleaseService $releases,
        private readonly SkillParserService $parser,
        private readonly SkillManifestValidator $manifestValidator,
    ) {}

    public function index(Request $request, Skill $skill): JsonResponse
    {
        $this->authorize('view', $skill);
        $canManage = $request->user()?->can('manageVersions', $skill) ?? false;

        $query = $skill->versions()->orderByDesc('created_at');
        if (! $canManage) {
            $query->whereIn('lifecycle_status', SkillVersionStatus::INSTALLABLE);
        }

        $publicColumns = [
            'id',
            'skill_id',
            'version',
            'lifecycle_status',
            'package_hash',
            'verification_status',
            'release_notes',
            'based_on_version_id',
            'published_at',
            'deprecated_at',
            'suspended_at',
            'archived_at',
            'created_at',
            'updated_at',
        ];
        $managerColumns = [
            'content_hash',
            'manifest_hash',
            'instructions_hash',
            'source_repository',
            'source_branch',
            'source_commit_sha',
            'imported_at',
            'imported_by',
            'verification_details',
            'verified_at',
            'created_by',
            'published_by',
        ];

        return response()->json([
            'current_version_id' => $skill->current_version_id,
            'latest_published_version_id' => $skill->latest_published_version_id,
            'can_manage' => $canManage,
            'versions' => $query->get($canManage ? [...$publicColumns, ...$managerColumns] : $publicColumns),
        ]);
    }

    public function createDraft(Request $request, Skill $skill): JsonResponse
    {
        $this->authorize('manageVersions', $skill);
        $validated = $request->validate([
            'version' => ['sometimes', 'string', 'max:64'],
            'based_on_version_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $base = isset($validated['based_on_version_id'])
            ? $this->ownedVersion($skill, (int) $validated['based_on_version_id'])
            : null;

        try {
            $version = $this->releases->createDraft(
                $skill,
                $request->user()?->id,
                $base,
                $validated['version'] ?? null,
            );
        } catch (InvalidArgumentException|LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('Draft version created.'), 'version' => $version], 201);
    }

    public function updateDraft(Request $request, Skill $skill, SkillVersion $skillVersion): JsonResponse
    {
        $this->authorize('manageVersions', $skill);
        $this->assertOwned($skill, $skillVersion);

        $validated = $request->validate([
            'version' => ['sometimes', 'string', 'max:64'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:2000'],
            'instructions' => ['sometimes', 'string'],
            'bundled_resources' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'inputs' => ['sometimes', 'array'],
            'outputs' => ['sometimes', 'array'],
            'tools' => ['sometimes', 'array'],
            'capabilities' => ['sometimes', 'array'],
            'capabilities.*' => ['string', 'max:191'],
            'source_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'release_notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ]);

        if (isset($validated['instructions'])) {
            $validated['instructions'] = $this->parser->sanitizeInstructions($validated['instructions']);
        }

        try {
            $validated = $this->normalizeDraftContract($skillVersion, $validated);
            $version = $this->releases->updateDraft($skillVersion, $validated);
        } catch (InvalidArgumentException|LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('Draft version updated.'), 'version' => $version]);
    }

    public function publish(Request $request, Skill $skill, SkillVersion $skillVersion): JsonResponse
    {
        $this->authorize('publish', $skill);
        $this->assertOwned($skill, $skillVersion);
        $validated = $request->validate(['release_notes' => ['sometimes', 'nullable', 'string', 'max:10000']]);

        try {
            $version = $this->releases->publish($skillVersion, $request->user()?->id, $validated['release_notes'] ?? null);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('Skill version published.'), 'version' => $version]);
    }

    public function verify(Request $request, Skill $skill, SkillVersion $skillVersion): JsonResponse
    {
        $this->authorize('manageVersions', $skill);
        $this->assertOwned($skill, $skillVersion);

        $result = $this->releases->verifyPackage($skillVersion);

        return response()->json([
            'message' => $result->isVerified()
                ? __('Skill package integrity verified.')
                : __('Skill package integrity verification found differences.'),
            'verification' => $result->toArray(),
            'version' => $skillVersion->fresh(['resources']),
        ], $result->isVerified() ? 200 : 409);
    }

    public function rollback(Request $request, Skill $skill, SkillVersion $skillVersion): JsonResponse
    {
        $this->authorize('manageVersions', $skill);
        $this->assertOwned($skill, $skillVersion);

        try {
            $version = $this->releases->rollback($skill, $skillVersion, $request->user()?->id);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('Skill rolled back to the selected version.'), 'version' => $version]);
    }

    public function compare(Skill $skill, SkillVersion $skillVersion, SkillVersion $otherVersion): JsonResponse
    {
        $this->authorize('manageVersions', $skill);
        $this->assertOwned($skill, $skillVersion);
        $this->assertOwned($skill, $otherVersion);

        return response()->json([
            'from' => $skillVersion->version,
            'to' => $otherVersion->version,
            'differences' => $this->releases->compare($skillVersion, $otherVersion),
        ]);
    }

    public function transition(Request $request, Skill $skill, SkillVersion $skillVersion): JsonResponse
    {
        $this->authorize('publish', $skill);
        $this->assertOwned($skill, $skillVersion);
        $validated = $request->validate([
            'status' => ['required', 'in:draft,validating,review_required,approved,deprecated,suspended,archived,quarantined'],
        ]);

        try {
            $version = $this->releases->transition($skillVersion, $validated['status'], $request->user()?->id);
        } catch (InvalidArgumentException|LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('Skill version lifecycle updated.'), 'version' => $version]);
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    private function normalizeDraftContract(SkillVersion $version, array $validated): array
    {
        $contractFields = ['inputs', 'outputs', 'tools', 'capabilities'];
        $hasContractChange = array_intersect($contractFields, array_keys($validated)) !== [];
        $hasMetadataChange = array_key_exists('metadata', $validated);

        if (! $hasContractChange && ! $hasMetadataChange) {
            return $validated;
        }

        $versionMetadata = is_array($version->metadata) ? $version->metadata : [];
        $metadata = is_array($validated['metadata'] ?? null)
            ? $validated['metadata']
            : $versionMetadata;
        $existingContract = is_array($metadata['skill_contract'] ?? null)
            ? $metadata['skill_contract']
            : (is_array($versionMetadata['skill_contract'] ?? null) ? $versionMetadata['skill_contract'] : []);

        $manifest = $this->manifestValidator->validate([
            'name' => $validated['name'] ?? $version->name,
            'description' => $validated['description'] ?? $version->description,
            'instructions' => $validated['instructions'] ?? $version->instructions,
            'version' => $validated['version'] ?? $version->version,
            'metadata' => $metadata,
            'inputs' => $validated['inputs'] ?? $existingContract['inputs'] ?? ['type' => 'object', 'properties' => []],
            'outputs' => $validated['outputs'] ?? $existingContract['outputs'] ?? ['type' => 'object'],
            'tools' => $validated['tools'] ?? $existingContract['tools'] ?? ['allowed' => [], 'required' => []],
            'capabilities' => $validated['capabilities'] ?? $existingContract['capabilities'] ?? [],
        ])->toArray();

        $validated['metadata'] = $manifest['metadata'];
        foreach ($contractFields as $field) {
            unset($validated[$field]);
        }

        return $validated;
    }

    private function ownedVersion(Skill $skill, int $versionId): SkillVersion
    {
        return $skill->versions()->findOrFail($versionId);
    }

    private function assertOwned(Skill $skill, SkillVersion $version): void
    {
        abort_unless((int) $version->skill_id === (int) $skill->id, 404);
    }
}
