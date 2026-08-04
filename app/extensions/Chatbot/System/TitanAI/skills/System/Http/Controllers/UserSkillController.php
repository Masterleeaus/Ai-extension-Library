<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Http\Controllers;

use App\Extensions\SystemAIChatSkills\System\Contracts\CompanyContextResolverInterface;
use App\Extensions\SystemAIChatSkills\System\Http\Requests\ImportGithubSkillRequest;
use App\Extensions\SystemAIChatSkills\System\Http\Requests\StoreSkillRequest;
use App\Extensions\SystemAIChatSkills\System\Imports\SkillImportService;
use App\Extensions\SystemAIChatSkills\System\Models\CompanySkill;
use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use App\Extensions\SystemAIChatSkills\System\Models\SkillVersion;
use App\Extensions\SystemAIChatSkills\System\Packages\SkillPackageManifestSerializer;
use App\Extensions\SystemAIChatSkills\System\Packages\SkillResourceFingerprint;
use App\Extensions\SystemAIChatSkills\System\Services\SkillParserService;
use App\Extensions\SystemAIChatSkills\System\Services\SkillReleaseService;
use App\Extensions\SystemAIChatSkills\System\Skills\SkillManifestSerializer;
use App\Extensions\SystemAIChatSkills\System\Support\SkillOwnershipType;
use App\Extensions\SystemAIChatSkills\System\Support\SkillVisibility;
use App\Extensions\SystemAIChatSkills\System\Support\SkillUpdatePolicy;
use App\Extensions\SystemAIChatSkills\System\Tenancy\CompanyContext;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class UserSkillController extends Controller
{
    public function __construct(
        private SkillParserService $parser,
        private CompanyContextResolverInterface $companyResolver,
        private SkillManifestSerializer $manifestSerializer,
        private SkillPackageManifestSerializer $packageManifestSerializer,
        private SkillReleaseService $releases,
        private SkillImportService $imports,
    ) {}

    /**
     * Check if the user's plan allows skills usage.
     */
    private function userCanUseSkills($user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $plan = $user->relationPlan;

        return $plan && $plan->checkOpenAiItem('ai_chat_pro_skills');
    }

    /**
     * Auto-add seeded skills (e.g. Skill Creator) for the user if not already added.
     */
    private function ensureSeededSkillsAttached($user): void
    {
        $seededSkills = Skill::query()->active()->where('source', 'seeded')->get(['id', 'current_version_id']);

        if ($seededSkills->isEmpty()) {
            return;
        }

        $existingSeededIds = $user->belongsToMany(Skill::class, 'user_skills')
            ->whereIn('skills.id', $seededSkills->pluck('id'))
            ->pluck('skills.id');
        $missingSeeded = $seededSkills->whereNotIn('id', $existingSeededIds);

        if ($missingSeeded->isNotEmpty()) {
            $payload = [];
            foreach ($missingSeeded as $skill) {
                $payload[$skill->id] = [
                    'skill_version_id' => $skill->current_version_id,
                    'update_policy' => SkillUpdatePolicy::FOLLOW_COMPATIBLE,
                ];
            }
            $user->belongsToMany(Skill::class, 'user_skills')->attach($payload);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $this->companyResolver->resolve($user);
        $tab = $request->get('tab', 'all');
        $search = $request->get('search', '');

        $this->ensureSeededSkillsAttached($user);

        $userSkillPivots = $user->belongsToMany(Skill::class, 'user_skills')
            ->withPivot('auto_use', 'skill_version_id', 'update_policy')
            ->get()
            ->keyBy('id');
        $userSkillIds = $userSkillPivots->keys()->map(static fn ($id): int => (int) $id)->all();

        $companyInstallations = $this->companyInstallations($company);
        $accessibleCompanyInstallations = $companyInstallations->filter(
            fn (CompanySkill $installation): bool => $this->installationAccessible($installation, $company)
        );
        $eligibleCompanyInstallations = $companyInstallations->filter(
            fn (CompanySkill $installation): bool => $this->installationEligible($installation, $company)
        );
        $companyInstallationIds = ($company->isManager ? $eligibleCompanyInstallations : $accessibleCompanyInstallations)
            ->keys()
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $accessibleCompanyInstallationIds = $accessibleCompanyInstallations
            ->keys()
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $query = Skill::query()->active();

        if ($tab === 'public') {
            $query->where('visibility', SkillVisibility::PLATFORM_PUBLIC);
        } elseif ($tab === 'personal') {
            $query->where('ownership_type', SkillOwnershipType::USER)
                ->where('user_id', $user->id);
        } elseif ($tab === 'company') {
            if ($company->companyId === null) {
                $query->whereRaw('1 = 0');
            } else {
                $this->applyCompanyLibraryScope($query, $company, $companyInstallationIds);
            }
        } else {
            $this->applyAllLibraryScope($query, $user, $company, $accessibleCompanyInstallationIds);
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $skills = $query->get()->map(function (Skill $skill) use (
            $userSkillIds,
            $userSkillPivots,
            $companyInstallations,
            $company,
            $user,
        ): array {
            $isAdded = in_array((int) $skill->id, $userSkillIds, true);
            $autoUse = $isAdded
                ? (bool) ($userSkillPivots->get($skill->id)?->pivot?->auto_use ?? false)
                : false;
            $companyInstallation = $companyInstallations->get($skill->id);
            $isCompanyOwned = $company->companyId !== null
                && $skill->ownership_type === SkillOwnershipType::COMPANY
                && (int) $skill->company_id === $company->companyId;

            return [
                'id' => $skill->id,
                'name' => $skill->name,
                'slug' => $skill->slug,
                'description' => $skill->description,
                'source' => $skill->source,
                'is_public' => $skill->is_public,
                'ownership_type' => $skill->ownership_type,
                'visibility' => $skill->visibility,
                'company_id' => $skill->company_id,
                'is_added' => $isAdded,
                'auto_use' => $autoUse,
                'version' => $skill->version,
                'current_version_id' => $skill->current_version_id,
                'installed_version_id' => $isAdded ? $userSkillPivots->get($skill->id)?->pivot?->skill_version_id : null,
                'update_policy' => $isAdded ? $userSkillPivots->get($skill->id)?->pivot?->update_policy : null,
                'is_owner' => ($skill->ownership_type === SkillOwnershipType::USER && (int) $skill->user_id === (int) $user->id)
                    || ($isCompanyOwned && $company->isManager),
                'is_company_installed' => $companyInstallation !== null,
                'company_enabled' => $companyInstallation !== null ? (bool) $companyInstallation->is_enabled : null,
                'company_auto_use' => $companyInstallation !== null ? (bool) $companyInstallation->auto_use : null,
                'company_version_id' => $companyInstallation?->skill_version_id,
                'company_update_policy' => $companyInstallation?->update_policy,
            ];
        });

        $personalCount = Skill::query()->active()
            ->where('ownership_type', SkillOwnershipType::USER)
            ->where('user_id', $user->id)
            ->count();
        $publicCount = Skill::query()->active()
            ->where('visibility', SkillVisibility::PLATFORM_PUBLIC)
            ->count();
        $companyCount = 0;

        if ($company->companyId !== null) {
            $companyQuery = Skill::query()->active();
            $this->applyCompanyLibraryScope($companyQuery, $company, $companyInstallationIds);
            $companyCount = $companyQuery->count();
        }

        $allQuery = Skill::query()->active();
        $this->applyAllLibraryScope($allQuery, $user, $company, $accessibleCompanyInstallationIds);
        $allCount = $allQuery->distinct()->count('skills.id');

        return response()->json([
            'skills' => $skills,
            'counts' => [
                'all' => $allCount,
                'public' => $publicCount,
                'personal' => $personalCount,
                'company' => $companyCount,
            ],
            'company' => [
                'id' => $company->companyId,
                'role' => $company->role,
                'is_manager' => $company->isManager,
            ],
        ]);
    }

    public function addSkill(Request $request, Skill $skill): JsonResponse
    {
        $user = $request->user();

        if (! $this->userCanUseSkills($user)) {
            return response()->json(['message' => __('Your current plan does not include Skills. Please upgrade your plan.')], 403);
        }

        $this->authorize('add', $skill);

        if ($user->belongsToMany(Skill::class, 'user_skills')->where('skills.id', $skill->id)->exists()) {
            return response()->json(['message' => __('Skill already added.')], 409);
        }

        $user->belongsToMany(Skill::class, 'user_skills')->attach($skill->id, [
            'skill_version_id' => $skill->current_version_id,
            'update_policy' => SkillUpdatePolicy::FOLLOW_COMPATIBLE,
        ]);

        return response()->json(['message' => __('Skill added successfully.')]);
    }

    public function removeSkill(Request $request, Skill $skill): JsonResponse
    {
        $this->authorize('remove', $skill);

        $request->user()->belongsToMany(Skill::class, 'user_skills')->detach($skill->id);

        return response()->json(['message' => __('Skill removed.')]);
    }

    public function toggleAutoUse(Request $request, Skill $skill): JsonResponse
    {
        $user = $request->user();

        if (! $this->userCanUseSkills($user)) {
            return response()->json(['message' => __('Your current plan does not include Skills. Please upgrade your plan.')], 403);
        }

        $this->authorize('activate', $skill);

        $pivot = $user->belongsToMany(Skill::class, 'user_skills')
            ->withPivot('auto_use', 'skill_version_id', 'update_policy')
            ->find($skill->id);

        if (! $pivot) {
            return response()->json(['message' => __('Skill not added to your collection.')], 404);
        }

        $newValue = ! $pivot->pivot->auto_use;

        $user->belongsToMany(Skill::class, 'user_skills')
            ->updateExistingPivot($skill->id, ['auto_use' => $newValue]);

        return response()->json([
            'message'  => $newValue ? __('Auto-use enabled.') : __('Auto-use disabled.'),
            'auto_use' => $newValue,
        ]);
    }

    public function configureInstallation(Request $request, Skill $skill): JsonResponse
    {
        $this->authorize('activate', $skill);

        $validated = $request->validate([
            'skill_version_id' => ['sometimes', 'nullable', 'integer'],
            'update_policy' => ['sometimes', 'in:pinned,follow_compatible,follow_latest'],
        ]);

        $pivot = $request->user()->belongsToMany(Skill::class, 'user_skills')
            ->withPivot('skill_version_id', 'update_policy')
            ->find($skill->id);

        if (! $pivot) {
            return response()->json(['message' => __('Skill not added to your collection.')], 404);
        }

        if (array_key_exists('skill_version_id', $validated)) {
            $validated['skill_version_id'] = $this->validatedInstallVersionId($skill, $validated['skill_version_id']);
        }

        if (($validated['update_policy'] ?? null) === SkillUpdatePolicy::PINNED
            && ! array_key_exists('skill_version_id', $validated)) {
            $validated['skill_version_id'] = $pivot->pivot->skill_version_id ?: $skill->current_version_id;
        }

        $request->user()->belongsToMany(Skill::class, 'user_skills')
            ->updateExistingPivot($skill->id, $validated);

        return response()->json([
            'message' => __('Skill version settings updated.'),
            'skill_version_id' => $validated['skill_version_id'] ?? $pivot->pivot->skill_version_id,
            'update_policy' => $validated['update_policy'] ?? $pivot->pivot->update_policy,
        ]);
    }

    public function installForCompany(Request $request, Skill $skill): JsonResponse
    {
        $this->authorize('installForCompany', $skill);

        $company = $this->companyResolver->resolve($request->user());
        if ($company->companyId === null) {
            return response()->json(['message' => __('No active company workspace was found.')], 422);
        }

        $installation = CompanySkill::query()->updateOrCreate(
            [
                'company_id' => $company->companyId,
                'skill_id' => $skill->id,
            ],
            [
                'installed_by' => $request->user()->id,
                'is_enabled' => true,
                'skill_version_id' => $skill->current_version_id,
                'update_policy' => SkillUpdatePolicy::FOLLOW_COMPATIBLE,
            ]
        );

        return response()->json([
            'message' => __('Skill installed for the company.'),
            'installation' => $installation,
        ]);
    }

    public function removeFromCompany(Request $request, Skill $skill): JsonResponse
    {
        $this->authorize('removeFromCompany', $skill);

        $company = $this->companyResolver->resolve($request->user());
        CompanySkill::query()
            ->where('company_id', $company->companyId)
            ->where('skill_id', $skill->id)
            ->delete();

        return response()->json(['message' => __('Skill removed from the company.')]);
    }

    public function configureForCompany(Request $request, Skill $skill): JsonResponse
    {
        $this->authorize('configureCompany', $skill);

        $validated = $request->validate([
            'is_enabled' => ['sometimes', 'boolean'],
            'auto_use' => ['sometimes', 'boolean'],
            'allowed_roles' => ['sometimes', 'nullable', 'array'],
            'allowed_roles.*' => ['string', 'max:64'],
            'settings' => ['sometimes', 'nullable', 'array'],
            'skill_version_id' => ['sometimes', 'nullable', 'integer'],
            'update_policy' => ['sometimes', 'in:pinned,follow_compatible,follow_latest'],
        ]);

        if (array_key_exists('allowed_roles', $validated) && is_array($validated['allowed_roles'])) {
            $validated['allowed_roles'] = array_values(array_unique(array_map(
                static fn (string $role): string => trim($role),
                array_filter($validated['allowed_roles'], static fn (mixed $role): bool => is_string($role) && trim($role) !== '')
            )));
        }

        if (array_key_exists('skill_version_id', $validated)) {
            $validated['skill_version_id'] = $this->validatedInstallVersionId($skill, $validated['skill_version_id']);
        }

        $company = $this->companyResolver->resolve($request->user());
        $installation = CompanySkill::query()
            ->where('company_id', $company->companyId)
            ->where('skill_id', $skill->id)
            ->firstOrFail();

        if (($validated['update_policy'] ?? null) === SkillUpdatePolicy::PINNED
            && ! array_key_exists('skill_version_id', $validated)) {
            $validated['skill_version_id'] = $installation->skill_version_id ?: $skill->current_version_id;
        }

        $installation->update($validated);

        return response()->json([
            'message' => __('Company skill settings updated.'),
            'installation' => $installation->fresh(),
        ]);
    }

    public function upload(StoreSkillRequest $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json(['message' => __('This feature is disabled in Demo version.')], 403);
        }

        $user = $request->user();

        if (! $this->userCanUseSkills($user)) {
            return response()->json(['message' => __('Your current plan does not include Skills. Please upgrade your plan.')], 403);
        }

        $ownership = $this->creationOwnership($request);

        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');

            try {
                $parsed = $this->parser->parseSkillFile($uploadedFile);
            } catch (\InvalidArgumentException|RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            if (! $this->parser->validateSchema($parsed)) {
                return response()->json(['message' => __('Invalid skill file. SKILL.md must contain a name and instructions.')], 422);
            }

            $parsed['instructions'] = $this->parser->sanitizeInstructions($parsed['instructions']);
            $slug = Skill::uniqueSlug(
                $parsed['name'],
                $ownership['user_id'],
                $ownership['company_id'],
                $ownership['ownership_type'],
            );

            try {
                $result = $this->imports->import([
                    'user_id'            => $ownership['user_id'],
                    'company_id'         => $ownership['company_id'],
                    'ownership_type'     => $ownership['ownership_type'],
                    'visibility'         => $ownership['visibility'],
                    'created_by'         => $ownership['created_by'],
                    'managed_by'         => $ownership['managed_by'],
                    'name'               => $parsed['name'],
                    'slug'               => $slug,
                    'description'        => $parsed['description'] ?: $parsed['name'],
                    'instructions'       => $parsed['instructions'],
                    'version'            => $parsed['version'] ?? '1.0.0',
                    'metadata'           => $parsed['metadata'] ?? [],
                    'source'             => 'uploaded',
                    'is_public'          => false,
                    'status'             => 'active',
                ], $parsed['files'] ?? [], function (Skill $skill) use ($request, $ownership): void {
                    $this->attachCreatedSkill($request, $skill, $ownership);
                }, $parsed['import_report'] ?? []);
            } catch (\InvalidArgumentException|RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'message' => __('Skill uploaded successfully.'),
                'skill'   => $result->skill,
                'import_report' => $result->report,
            ]);
        }

        return response()->json(['message' => __('No file provided.')], 422);
    }

    public function importFromGithub(ImportGithubSkillRequest $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json(['message' => __('This feature is disabled in Demo version.')], 403);
        }

        $user = $request->user();

        if (! $this->userCanUseSkills($user)) {
            return response()->json(['message' => __('Your current plan does not include Skills. Please upgrade your plan.')], 403);
        }

        $ownership = $this->creationOwnership($request);

        try {
            $parsed = $this->parser->parseGithubUrl($request->input('url'));
        } catch (\InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! $this->parser->validateSchema($parsed)) {
            return response()->json(['message' => __('Invalid skill file. SKILL.md must contain a name and instructions.')], 422);
        }

        $parsed['instructions'] = $this->parser->sanitizeInstructions($parsed['instructions']);
        $slug = Skill::uniqueSlug(
            $parsed['name'],
            $ownership['user_id'],
            $ownership['company_id'],
            $ownership['ownership_type'],
        );

        try {
            $result = $this->imports->import([
                'user_id'            => $ownership['user_id'],
                'company_id'         => $ownership['company_id'],
                'ownership_type'     => $ownership['ownership_type'],
                'visibility'         => $ownership['visibility'],
                'created_by'         => $ownership['created_by'],
                'managed_by'         => $ownership['managed_by'],
                'name'               => $parsed['name'],
                'slug'               => $slug,
                'description'        => $parsed['description'] ?: $parsed['name'],
                'instructions'       => $parsed['instructions'],
                'version'            => $parsed['version'] ?? '1.0.0',
                'metadata'           => $parsed['metadata'] ?? [],
                'source'             => 'github',
                'source_url'         => $request->input('url'),
                'is_public'          => false,
                'status'             => 'active',
            ], $parsed['files'] ?? [], function (Skill $skill) use ($request, $ownership): void {
                $this->attachCreatedSkill($request, $skill, $ownership);
            }, $parsed['import_report'] ?? []);
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
            return response()->json(['message' => __('No skill files (.md) found in this repository.')], 422);
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
            'ownership_type' => ['sometimes', 'in:user,company'],
            'visibility' => ['sometimes', 'in:company_private,company_shared'],
        ]);

        $user = $request->user();

        if (! $this->userCanUseSkills($user)) {
            return response()->json(['message' => __('Your current plan does not include Skills. Please upgrade your plan.')], 403);
        }

        $ownership = $this->creationOwnership($request);
        $url = $request->input('url');
        $skillFolders = $request->input('files');
        $imported = 0;
        $errors = [];

        foreach ($skillFolders as $skillFolder) {
            try {
                // Each entry is a skill folder path (containing SKILL.md)
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
                $slug = Skill::uniqueSlug(
                    $parsed['name'],
                    $ownership['user_id'],
                    $ownership['company_id'],
                    $ownership['ownership_type'],
                );

                $this->imports->import([
                    'user_id'            => $ownership['user_id'],
                    'company_id'         => $ownership['company_id'],
                    'ownership_type'     => $ownership['ownership_type'],
                    'visibility'         => $ownership['visibility'],
                    'created_by'         => $ownership['created_by'],
                    'managed_by'         => $ownership['managed_by'],
                    'name'               => $parsed['name'],
                    'slug'               => $slug,
                    'description'        => $parsed['description'] ?: $parsed['name'],
                    'instructions'       => $parsed['instructions'],
                    'version'            => $parsed['version'] ?? '1.0.0',
                    'metadata'           => $parsed['metadata'] ?? [],
                    'source'             => 'github',
                    'source_url'         => $url,
                    'is_public'          => false,
                    'status'             => 'active',
                ], $parsed['files'] ?? [], function (Skill $skill) use ($request, $ownership): void {
                    $this->attachCreatedSkill($request, $skill, $ownership);
                }, $parsed['import_report'] ?? []);
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

    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $this->companyResolver->resolve($user);
        $q = $request->get('q', '');

        $this->ensureSeededSkillsAttached($user);

        $skillIds = $user->belongsToMany(Skill::class, 'user_skills')
            ->pluck('skills.id')
            ->map(static fn ($id): int => (int) $id);

        if ($company->companyId !== null) {
            $installationIds = $this->companyInstallations($company)
                ->filter(fn (CompanySkill $installation): bool => $this->installationAccessible($installation, $company))
                ->keys()
                ->map(static fn ($id): int => (int) $id);

            $companyOwnedIds = Skill::query()
                ->active()
                ->where('ownership_type', SkillOwnershipType::COMPANY)
                ->where('company_id', $company->companyId)
                ->whereIn('visibility', $company->isManager
                    ? [SkillVisibility::COMPANY_PRIVATE, SkillVisibility::COMPANY_SHARED]
                    : [SkillVisibility::COMPANY_SHARED])
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id);

            $skillIds = $skillIds->merge($installationIds)->merge($companyOwnedIds);
        }

        $skills = Skill::query()
            ->active()
            ->whereIn('id', $skillIds->unique()->values()->all())
            ->when($q, function ($query, $q): void {
                $query->where(function ($query) use ($q): void {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->get(['id', 'name', 'description', 'slug', 'ownership_type', 'visibility', 'company_id'])
            ->map(function (Skill $skill): array {
                return [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'slug' => $skill->slug,
                    'description' => Str::limit($skill->description, 80),
                    'ownership_type' => $skill->ownership_type,
                    'visibility' => $skill->visibility,
                    'company_id' => $skill->company_id,
                ];
            });

        return response()->json(['skills' => $skills]);
    }

    public function deleteSkill(Request $request, Skill $skill): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json(['message' => __('This feature is disabled in Demo version.')], 403);
        }

        $this->authorize('delete', $skill);

        $skill->users()->detach();
        $skill->companyInstallations()->delete();
        $skill->delete();

        return response()->json([
            'message' => __('Skill deleted successfully.'),
        ]);
    }

    public function publicSearch(Request $request): JsonResponse
    {
        $q = $request->get('q', '');

        $skills = Skill::query()
            ->active()
            ->public()
            ->when($q, function ($query, $q) {
                $query->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->get(['id', 'name', 'description', 'slug'])
            ->map(function (Skill $skill) {
                return [
                    'id'          => $skill->id,
                    'name'        => $skill->name,
                    'slug'        => $skill->slug,
                    'description' => Str::limit($skill->description, 80),
                ];
            });

        return response()->json(['skills' => $skills]);
    }

    public function publicList(Request $request): JsonResponse
    {
        $search = $request->get('search', '');

        $skills = Skill::query()
            ->active()
            ->public()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->get(['id', 'name', 'slug', 'description', 'source', 'is_public'])
            ->map(function (Skill $skill) {
                return [
                    'id'          => $skill->id,
                    'name'        => $skill->name,
                    'slug'        => $skill->slug,
                    'description' => $skill->description,
                    'source'      => $skill->source,
                    'is_public'   => $skill->is_public,
                    'is_added'    => false,
                    'auto_use'    => false,
                    'is_owner'    => false,
                ];
            });

        $counts = [
            'all'      => Skill::query()->active()->public()->count(),
            'public'   => Skill::query()->active()->public()->count(),
            'personal' => 0,
        ];

        return response()->json([
            'skills' => $skills,
            'counts' => $counts,
        ]);
    }

    public function show(Request $request, Skill $skill): JsonResponse
    {
        $this->authorize('view', $skill);

        return $this->skillDetailResponse($skill);
    }

    public function publicShow(Skill $skill): JsonResponse
    {
        $this->authorize('preview', $skill);

        return $this->skillDetailResponse($skill);
    }

    /**
     * Create a skill from raw content (used by Skill Creator "Add" button in chat).
     */
    public function createFromContent(Request $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json(['message' => __('This feature is disabled in Demo version.')], 403);
        }

        $user = $request->user();

        if (! $this->userCanUseSkills($user)) {
            return response()->json(['message' => __('Your current plan does not include Skills. Please upgrade your plan.')], 403);
        }

        $request->validate([
            'ownership_type' => ['sometimes', 'in:user,company'],
            'visibility' => ['sometimes', 'in:company_private,company_shared'],
        ]);
        $ownership = $this->creationOwnership($request);
        $files = $request->input('files', []);

        if (! is_array($files) || empty($files)) {
            return response()->json(['message' => __('No skill content provided.')], 422);
        }

        // Validate file entries have required keys
        foreach ($files as $file) {
            if (! is_array($file) || empty($file['path']) || ! isset($file['content'])) {
                return response()->json(['message' => __('Invalid file format.')], 422);
            }
        }

        try {
            $inspection = $this->parser->inspectFiles($files, ['source' => 'generated']);
        } catch (\InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $skillMdContent = null;
        foreach ($inspection['files'] as $file) {
            if (($file['path'] ?? null) === 'SKILL.md') {
                $skillMdContent = $file['content'];
                break;
            }
        }

        if (! is_string($skillMdContent)) {
            return response()->json(['message' => __('SKILL.md is required.')], 422);
        }

        $parsed = Skill::parseSkillMd($skillMdContent);
        $parsed['metadata']['import_report'] = $inspection['import_report'];

        if (! $this->parser->validateSchema($parsed)) {
            return response()->json(['message' => __('Invalid skill. Name and instructions are required.')], 422);
        }

        $parsed['instructions'] = $this->parser->sanitizeInstructions($parsed['instructions']);
        $slug = Skill::uniqueSlug(
            $parsed['name'],
            $ownership['user_id'],
            $ownership['company_id'],
            $ownership['ownership_type'],
        );

        try {
            $result = $this->imports->import([
                'user_id'            => $ownership['user_id'],
                'company_id'         => $ownership['company_id'],
                'ownership_type'     => $ownership['ownership_type'],
                'visibility'         => $ownership['visibility'],
                'created_by'         => $ownership['created_by'],
                'managed_by'         => $ownership['managed_by'],
                'name'               => $parsed['name'],
                'slug'               => $slug,
                'description'        => $parsed['description'] ?: $parsed['name'],
                'instructions'       => $parsed['instructions'],
                'version'            => $parsed['version'] ?? '1.0.0',
                'metadata'           => $parsed['metadata'] ?? [],
                'source'             => 'created',
                'is_public'          => false,
                'status'             => 'active',
            ], $inspection['files'], function (Skill $skill) use ($request, $ownership): void {
                $this->attachCreatedSkill($request, $skill, $ownership);
            }, $inspection['import_report']);
        } catch (\InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('Skill added successfully.'),
            'skill'   => $result->skill,
            'import_report' => $result->report,
        ]);
    }

    /**
     * Export/download a skill as a .skill file (zip format with .skill extension).
     * Compatible with Claude, OpenAI, and other skill platforms.
     */
    public function export(Request $request, Skill $skill): Response
    {
        $this->authorize('export', $skill);

        $activeVersion = $skill->effectiveVersion();
        if ($activeVersion === null || ! $activeVersion->isInstallable()) {
            return response()->json(['message' => __('This skill does not have an installable release to export.')], 409);
        }

        $verification = $this->releases->verifyPackage($activeVersion);
        if (! $verification->isVerified()) {
            return response()->json([
                'message' => __('The skill package failed integrity verification and cannot be exported.'),
                'verification' => $verification->toArray(),
            ], 409);
        }

        $storagePath = $skill->absoluteStoragePath();
        $zipPath = tempnam(sys_get_temp_dir(), 'skill_') . '.skill';
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return response()->json(['message' => __('Failed to create export file.')], 500);
        }

        // Always reconstruct SKILL.md from the active release shadow fields.
        // Stored source files may represent an older imported release after publish or rollback.
        $zip->addFromString('SKILL.md', $this->reconstructSkillMd($skill));
        $zip->addFromString(
            'PACKAGE-MANIFEST.json',
            $this->packageManifestSerializer->serialize($skill, $activeVersion->fresh(['resources'])),
        );

        // Add bundled resource files
        if (is_dir($storagePath) && ! empty($skill->bundled_resources)) {
            foreach ($skill->bundled_resources as $folder => $files) {
                foreach ($files as $file) {
                    $fileName = is_array($file) ? ($file['path'] ?? $file['name']) : $file;
                    $filePath = $storagePath . '/' . $fileName;

                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, $fileName);
                    }
                }
            }
        }

        $zip->close();

        return response()->download($zipPath, $skill->slug . '.skill')->deleteFileAfterSend(true);
    }

    /**
     * Get the content of a specific bundled resource file.
     */
    public function fileContent(Request $request, Skill $skill): JsonResponse
    {
        $this->authorize('readResource', $skill);

        $filePath = $request->get('path', '');

        if ($filePath === '' || str_contains($filePath, '..') || str_starts_with($filePath, '/')) {
            return response()->json(['content' => ''], 400);
        }

        $storagePath = $skill->absoluteStoragePath();
        $basePath = realpath($storagePath);

        if ($basePath === false) {
            return response()->json(['content' => '']);
        }

        $fullPath = realpath($basePath . DIRECTORY_SEPARATOR . $filePath);

        // Ensure the resolved file is a child of this skill's real storage directory.
        if ($fullPath === false || ! str_starts_with($fullPath, $basePath . DIRECTORY_SEPARATOR)) {
            return response()->json(['content' => '']);
        }

        if (! is_file($fullPath)) {
            return response()->json(['content' => '']);
        }

        try {
            $normalisedPath = SkillResourceFingerprint::normalisePath($filePath);
        } catch (InvalidArgumentException) {
            return response()->json(['content' => ''], 400);
        }

        $activeVersion = $skill->effectiveVersion();
        $recordedResource = $activeVersion?->resources()->where('path', $normalisedPath)->first();
        if ($recordedResource !== null) {
            $actualHash = hash_file('sha256', $fullPath);
            if (! is_string($actualHash) || ! hash_equals($recordedResource->sha256, $actualHash)) {
                if ($activeVersion !== null) {
                    $this->releases->verifyPackage($activeVersion);
                }

                return response()->json([
                    'message' => __('This bundled resource failed integrity verification.'),
                    'content' => '',
                ], 409);
            }
        }

        return response()->json([
            'content' => file_get_contents($fullPath),
            'sha256' => $recordedResource?->sha256,
        ]);
    }

    /** @return Collection<int, CompanySkill> */
    private function companyInstallations(CompanyContext $company): Collection
    {
        if ($company->companyId === null) {
            return collect();
        }

        return CompanySkill::query()
            ->with('skill')
            ->where('company_id', $company->companyId)
            ->get()
            ->keyBy('skill_id');
    }

    private function installationAccessible(CompanySkill $installation, CompanyContext $company): bool
    {
        if (! $installation->is_enabled || ! $this->installationEligible($installation, $company)) {
            return false;
        }

        $roles = $installation->allowed_roles;

        return $company->isManager
            || $roles === null
            || $roles === []
            || ($company->role !== null && in_array($company->role, $roles, true));
    }

    private function installationEligible(CompanySkill $installation, CompanyContext $company): bool
    {
        $skill = $installation->skill;

        if ($skill === null || $skill->status !== 'active') {
            return false;
        }

        if ($skill->ownership_type === SkillOwnershipType::PLATFORM) {
            return $skill->visibility === SkillVisibility::PLATFORM_PUBLIC;
        }

        return $skill->ownership_type === SkillOwnershipType::COMPANY
            && $company->companyId !== null
            && (int) $skill->company_id === $company->companyId;
    }

    /** @param list<int> $installationIds */
    private function applyCompanyLibraryScope($query, CompanyContext $company, array $installationIds): void
    {
        $query->where(function ($query) use ($company, $installationIds): void {
            $query->where(function ($query) use ($company): void {
                $query->where('ownership_type', SkillOwnershipType::COMPANY)
                    ->where('company_id', $company->companyId)
                    ->whereIn('visibility', $company->isManager
                        ? [SkillVisibility::COMPANY_PRIVATE, SkillVisibility::COMPANY_SHARED]
                        : [SkillVisibility::COMPANY_SHARED]);
            });

            if ($installationIds !== []) {
                $query->orWhereIn('id', $installationIds);
            }
        });
    }

    /** @param list<int> $installationIds */
    private function applyAllLibraryScope($query, $user, CompanyContext $company, array $installationIds): void
    {
        $query->where(function ($query) use ($user, $company, $installationIds): void {
            $query->where(function ($query): void {
                $query->where('ownership_type', SkillOwnershipType::PLATFORM)
                    ->where('visibility', SkillVisibility::PLATFORM_PUBLIC);
            })->orWhere(function ($query) use ($user): void {
                $query->where('ownership_type', SkillOwnershipType::USER)
                    ->where('user_id', $user->id)
                    ->where('visibility', SkillVisibility::PRIVATE_USER);
            });

            if ($company->companyId !== null) {
                $query->orWhere(function ($query) use ($company): void {
                    $query->where('ownership_type', SkillOwnershipType::COMPANY)
                        ->where('company_id', $company->companyId)
                        ->whereIn('visibility', $company->isManager
                            ? [SkillVisibility::COMPANY_PRIVATE, SkillVisibility::COMPANY_SHARED]
                            : [SkillVisibility::COMPANY_SHARED]);
                });
            }

            if ($installationIds !== []) {
                $query->orWhereIn('id', $installationIds);
            }
        });
    }

    /**
     * @return array{user_id: int|null, company_id: int|null, ownership_type: string, visibility: string, created_by: int, managed_by: int, storage_owner: int|string}
     */
    private function creationOwnership(Request $request): array
    {
        $user = $request->user();
        $requestedOwnership = $request->input('ownership_type', SkillOwnershipType::USER);

        if ($requestedOwnership === SkillOwnershipType::COMPANY) {
            $company = $this->companyResolver->resolve($user);
            abort_unless($company->companyId !== null && $company->isManager, 403, __('Only company managers can create company-owned skills.'));

            $visibility = $request->input('visibility', SkillVisibility::COMPANY_PRIVATE);
            abort_unless(in_array($visibility, [SkillVisibility::COMPANY_PRIVATE, SkillVisibility::COMPANY_SHARED], true), 422, __('Invalid company skill visibility.'));

            return [
                'user_id' => null,
                'company_id' => $company->companyId,
                'ownership_type' => SkillOwnershipType::COMPANY,
                'visibility' => $visibility,
                'created_by' => (int) $user->id,
                'managed_by' => (int) $user->id,
                'storage_owner' => 'company-' . $company->companyId,
            ];
        }

        return [
            'user_id' => (int) $user->id,
            'company_id' => null,
            'ownership_type' => SkillOwnershipType::USER,
            'visibility' => SkillVisibility::PRIVATE_USER,
            'created_by' => (int) $user->id,
            'managed_by' => (int) $user->id,
            'storage_owner' => (int) $user->id,
        ];
    }

    /** @param array{ownership_type: string, company_id: int|null} $ownership */
    private function attachCreatedSkill(Request $request, Skill $skill, array $ownership): void
    {
        if ($ownership['ownership_type'] === SkillOwnershipType::COMPANY && $ownership['company_id'] !== null) {
            CompanySkill::query()->firstOrCreate([
                'company_id' => $ownership['company_id'],
                'skill_id' => $skill->id,
            ], [
                'installed_by' => $request->user()->id,
                'is_enabled' => true,
                'skill_version_id' => $skill->current_version_id,
                'update_policy' => SkillUpdatePolicy::FOLLOW_COMPATIBLE,
            ]);

            return;
        }

        $request->user()->belongsToMany(Skill::class, 'user_skills')->syncWithoutDetaching([
            $skill->id => [
                'skill_version_id' => $skill->current_version_id,
                'update_policy' => SkillUpdatePolicy::FOLLOW_COMPATIBLE,
            ],
        ]);
    }

    private function validatedInstallVersionId(Skill $skill, mixed $versionId): ?int
    {
        if ($versionId === null) {
            return null;
        }

        $version = SkillVersion::query()
            ->where('skill_id', $skill->id)
            ->whereKey((int) $versionId)
            ->first();

        if ($version === null || ! $version->isInstallable()) {
            abort(422, __('The selected skill version is not installable.'));
        }

        return (int) $version->id;
    }

    private function reconstructSkillMd(Skill $skill): string
    {
        return $this->manifestSerializer->serialize(
            $skill->name,
            $skill->description,
            $skill->version ?: '1.0.0',
            $skill->instructions,
            is_array($skill->metadata) ? $skill->metadata : [],
        );
    }

    private function skillDetailResponse(Skill $skill): JsonResponse
    {
        $metadata = is_array($skill->metadata) ? $skill->metadata : [];
        unset($metadata['provenance'], $metadata['package_manifest'], $metadata['verification']);

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
                'package_hash'      => $skill->package_hash,
                'verification_status' => $skill->verification_status,
                'metadata'          => $metadata,
                'skill_contract'    => is_array($metadata['skill_contract'] ?? null)
                    ? $metadata['skill_contract']
                    : null,
                'created_at'        => $skill->created_at->toDateTimeString(),
            ],
        ]);
    }
}
