<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Services;

use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use App\Extensions\SystemAIChatSkills\System\Models\SkillVersion;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolDefinition;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolDefinitionMerger;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolExecutionContext;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolExecutionResult;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolExecutionService;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolInstructionCompiler;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolRegistry;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolSet;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class SkillToolService
{
    /** @param Collection<int, Skill> $skills */
    public static function openAiTools(Collection $skills): array
    {
        $providerTools = [];

        foreach ($skills as $skill) {
            $version = self::resolvedVersion($skill);
            $contract = self::contract($skill, $version);
            $providerTools[] = [
                'type' => 'function',
                'name' => self::slugToFunctionName($skill->slug),
                'description' => self::description($skill, $version, $contract['tools']),
                'parameters' => $contract['inputs'],
            ];

            foreach ($contract['tools']->definitions() as $declaredTool) {
                $tool = self::runtimeToolDefinition($declaredTool);
                $providerTools[] = [
                    'type' => 'function',
                    'name' => self::toolFunctionName($skill->slug, $tool->key),
                    'description' => self::toolDescription($skill, $tool, $contract['tools']),
                    'parameters' => $tool->inputSchema,
                ];
            }
        }

        return $providerTools;
    }

    /** @param Collection<int, Skill> $skills */
    public static function anthropicTools(Collection $skills): array
    {
        $providerTools = [];

        foreach ($skills as $skill) {
            $version = self::resolvedVersion($skill);
            $contract = self::contract($skill, $version);
            $providerTools[] = [
                'name' => self::slugToFunctionName($skill->slug),
                'description' => self::description($skill, $version, $contract['tools']),
                'input_schema' => $contract['inputs'],
            ];

            foreach ($contract['tools']->definitions() as $declaredTool) {
                $tool = self::runtimeToolDefinition($declaredTool);
                $providerTools[] = [
                    'name' => self::toolFunctionName($skill->slug, $tool->key),
                    'description' => self::toolDescription($skill, $tool, $contract['tools']),
                    'input_schema' => $tool->inputSchema,
                ];
            }
        }

        return $providerTools;
    }

    /** @param Collection<int, Skill> $skills */
    public static function geminiTools(Collection $skills): array
    {
        $declarations = [];

        foreach ($skills as $skill) {
            $version = self::resolvedVersion($skill);
            $contract = self::contract($skill, $version);
            $declarations[] = [
                'name' => self::slugToFunctionName($skill->slug),
                'description' => self::description($skill, $version, $contract['tools']),
                'parameters' => $contract['inputs'],
            ];

            foreach ($contract['tools']->definitions() as $declaredTool) {
                $tool = self::runtimeToolDefinition($declaredTool);
                $declarations[] = [
                    'name' => self::toolFunctionName($skill->slug, $tool->key),
                    'description' => self::toolDescription($skill, $tool, $contract['tools']),
                    'parameters' => $tool->inputSchema,
                ];
            }
        }

        return $declarations === [] ? [] : [['functionDeclarations' => $declarations]];
    }

    /** @param Collection<int, Skill> $skills */
    public static function handleSkillCall(string $functionName, Collection $skills): ?string
    {
        if (self::isToolFunction($functionName)) {
            return null;
        }

        $skill = self::findSkill($functionName, $skills);
        if ($skill === null) {
            return null;
        }

        $version = self::resolvedVersion($skill);
        $instructions = $version?->instructions ?? $skill->instructions;
        $contract = self::contract($skill, $version);

        return (new SkillToolInstructionCompiler)->compile(
            $instructions,
            $contract['tools'],
            $contract['capabilities'],
        );
    }

    /** @param Collection<int, Skill> $skills */
    public static function getSkillMeta(string $functionName, Collection $skills): ?array
    {
        $resolvedTool = self::resolveToolFunction($functionName, $skills);
        $skill = $resolvedTool['skill'] ?? self::findSkill($functionName, $skills);
        if (! $skill instanceof Skill) {
            return null;
        }

        $version = self::resolvedVersion($skill);
        $contract = self::contract($skill, $version);

        return [
            'name' => $version?->name ?? $skill->name,
            'slug' => $skill->slug,
            'version' => $version?->version ?? $skill->version,
            'skill_version_id' => $version?->id,
            'tool_key' => $resolvedTool['tool']->key ?? null,
            'tool_keys' => $contract['tools']->keys(),
            'required_tool_keys' => $contract['tools']->requiredKeys(),
            'required_capabilities' => $contract['capabilities'],
        ];
    }

    /** @param array<string, mixed> $arguments */
    public static function executeToolCall(
        Skill $skill,
        string $toolKey,
        array $arguments,
        SkillToolExecutionContext $context,
    ): SkillToolExecutionResult {
        $version = self::resolvedVersion($skill);
        $contract = self::contract($skill, $version);

        if (! app()->bound(SkillToolExecutionService::class)) {
            return SkillToolExecutionResult::unavailable('The skill tool execution runtime is not registered.');
        }

        return app(SkillToolExecutionService::class)->execute(
            $contract['tools'],
            $toolKey,
            $arguments,
            $context,
        );
    }

    /** @param Collection<int, Skill> $skills @param array<string, mixed> $arguments */
    public static function executeToolFunction(
        string $functionName,
        array $arguments,
        Collection $skills,
        SkillToolExecutionContext $context,
    ): SkillToolExecutionResult {
        $resolved = self::resolveToolFunction($functionName, $skills);
        if ($resolved === null) {
            return SkillToolExecutionResult::unavailable("Skill tool function [{$functionName}] is not available.");
        }

        return self::executeToolCall($resolved['skill'], $resolved['tool']->key, $arguments, $context);
    }

    public static function isToolFunction(string $functionName): bool
    {
        return str_starts_with($functionName, 'use_skill_') && str_contains($functionName, '__tool__');
    }

    public static function toolFunctionName(string $skillSlug, string $toolKey): string
    {
        $safeSlug = preg_replace('/[^A-Za-z0-9_]+/', '_', $skillSlug) ?: 'skill';
        $safeSlug = trim(substr($safeSlug, 0, 48), '_') ?: 'skill';
        $digest = substr(hash('sha256', $skillSlug . "\0" . $toolKey), 0, 24);

        return 'use_skill_' . $safeSlug . '__tool__' . $digest;
    }

    public static function functionNameToSlug(string $functionName): string
    {
        $withoutPrefix = preg_replace('/^use_skill_/', '', $functionName);

        return str_replace('_', '-', (string) $withoutPrefix);
    }

    public static function slugToFunctionName(string $slug): string
    {
        return 'use_skill_' . str_replace('-', '_', $slug);
    }

    /** @param Collection<int, Skill> $skills */
    private static function findSkill(string $functionName, Collection $skills): ?Skill
    {
        /** @var Skill|null $skill */
        $skill = $skills->first(
            fn (Skill $candidate): bool => self::slugToFunctionName($candidate->slug) === $functionName,
        );

        return $skill;
    }

    /** @param Collection<int, Skill> $skills @return array{skill: Skill, tool: SkillToolDefinition}|null */
    private static function resolveToolFunction(string $functionName, Collection $skills): ?array
    {
        if (! self::isToolFunction($functionName)) {
            return null;
        }

        foreach ($skills as $skill) {
            $contract = self::contract($skill, self::resolvedVersion($skill));
            foreach ($contract['tools']->definitions() as $declaredTool) {
                if (self::toolFunctionName($skill->slug, $declaredTool->key) === $functionName) {
                    return ['skill' => $skill, 'tool' => self::runtimeToolDefinition($declaredTool)];
                }
            }
        }

        return null;
    }

    private static function resolvedVersion(Skill $skill): ?SkillVersion
    {
        $pinnedId = $skill->pivot?->skill_version_id;
        if ($pinnedId !== null) {
            $pinned = SkillVersion::query()
                ->where('skill_id', $skill->id)
                ->whereKey((int) $pinnedId)
                ->first();

            if ($pinned?->isInstallable()) {
                return $pinned;
            }
        }

        $current = $skill->relationLoaded('currentVersion')
            ? $skill->currentVersion
            : $skill->currentVersion()->first();

        return $current?->isInstallable() ? $current : null;
    }

    /**
     * @return array{inputs: array<string, mixed>, outputs: array<string, mixed>, tools: SkillToolSet, capabilities: list<string>}
     */
    private static function contract(Skill $skill, ?SkillVersion $version): array
    {
        $metadata = $version?->metadata ?? $skill->metadata ?? [];
        $contract = is_array($metadata['skill_contract'] ?? null) ? $metadata['skill_contract'] : [];

        $inputs = is_array($contract['inputs'] ?? null) ? $contract['inputs'] : [];
        $inputs['type'] = 'object';
        $inputs['properties'] = is_array($inputs['properties'] ?? null) ? $inputs['properties'] : [];

        $outputs = is_array($contract['outputs'] ?? null) ? $contract['outputs'] : ['type' => 'object'];
        $capabilities = array_values(array_filter(
            is_array($contract['capabilities'] ?? null) ? $contract['capabilities'] : [],
            static fn (mixed $capability): bool => is_string($capability) && trim($capability) !== '',
        ));

        try {
            $tools = SkillToolSet::fromArray(is_array($contract['tools'] ?? null) ? $contract['tools'] : []);
        } catch (InvalidArgumentException) {
            $tools = SkillToolSet::fromArray([]);
        }

        return [
            'inputs' => $inputs,
            'outputs' => $outputs,
            'tools' => $tools,
            'capabilities' => $capabilities,
        ];
    }

    private static function runtimeToolDefinition(SkillToolDefinition $declared): SkillToolDefinition
    {
        if (! function_exists('app')) {
            return $declared;
        }

        try {
            if (! app()->bound(SkillToolRegistry::class)) {
                return $declared;
            }

            return SkillToolDefinitionMerger::merge(
                $declared,
                app(SkillToolRegistry::class)->definition($declared->key),
            );
        } catch (\Throwable) {
            return $declared;
        }
    }

    private static function description(Skill $skill, ?SkillVersion $version, SkillToolSet $tools): string
    {
        $description = trim((string) ($version?->description ?? $skill->description));
        if ($tools->isEmpty()) {
            return $description;
        }

        return rtrim($description, '.') . '. Activates a governed skill with ' . count($tools->keys()) . ' declared tools.';
    }

    private static function toolDescription(Skill $skill, SkillToolDefinition $tool, SkillToolSet $tools): string
    {
        $flags = [];
        if ($tools->requires($tool->key)) {
            $flags[] = 'required by skill';
        }
        if ($tool->approval !== 'never') {
            $flags[] = 'approval ' . $tool->approval;
        }
        $suffix = $flags === [] ? '' : ' (' . implode(', ', $flags) . ')';

        return "Skill [{$skill->name}] tool {$tool->key}{$suffix}: {$tool->description}";
    }
}
