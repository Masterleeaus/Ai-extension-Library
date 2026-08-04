<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Imports;

use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SkillImportService
{
    public function __construct(private readonly SkillImportStagingArea $staging)
    {
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<int, array{path: string, content: string}> $files
     * @param (callable(Skill): void)|null $afterCreate
     * @param array<string, mixed> $report
     */
    public function import(array $attributes, array $files, ?callable $afterCreate = null, array $report = []): SkillImportResult
    {
        if ($files === []) {
            throw new \InvalidArgumentException('A skill import must contain at least SKILL.md.');
        }

        $staged = $this->staging->stage($files);
        $ownerScope = $attributes['ownership_type'] === 'company'
            ? 'company-' . (string) $attributes['company_id']
            : ($attributes['ownership_type'] === 'user' ? (int) $attributes['user_id'] : null);
        $finalPath = $this->staging->finalSkillPath($ownerScope, (string) $attributes['slug']);
        $promoted = false;

        try {
            $skill = DB::transaction(function () use (
                $attributes,
                $staged,
                $finalPath,
                &$promoted,
                $afterCreate,
            ): Skill {
                $this->staging->promote($staged, $finalPath);
                $promoted = true;

                $attributes['bundled_resources'] = $this->staging->describeResources($finalPath) ?: null;
                $skill = Skill::query()->create($attributes);

                if ($afterCreate !== null) {
                    $afterCreate($skill);
                }

                return $skill;
            }, 1);
        } catch (Throwable $exception) {
            if ($promoted) {
                $this->staging->deleteDirectory($finalPath);
            }
            throw $exception;
        } finally {
            $this->staging->deleteDirectory($staged->path);
        }

        return new SkillImportResult($skill, array_merge($report, [
            'transactional' => true,
            'staged' => true,
            'promoted' => true,
            'stored_resources' => array_sum(array_map('count', $skill->bundled_resources ?? [])),
        ]));
    }
}
