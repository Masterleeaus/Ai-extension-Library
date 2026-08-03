<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tools;

final class SkillToolDefinitionMerger
{
    public static function merge(
        SkillToolDefinition $declared,
        ?SkillToolDefinition $registered,
    ): SkillToolDefinition {
        if ($registered === null) {
            return $declared;
        }

        return new SkillToolDefinition(
            key: $declared->key,
            functionName: $registered->functionName,
            description: $declared->description === $declared->key
                ? $registered->description
                : $declared->description,
            source: $registered->source,
            inputSchema: self::isDefaultInputSchema($declared->inputSchema)
                ? $registered->inputSchema
                : $declared->inputSchema,
            outputSchema: self::isDefaultOutputSchema($declared->outputSchema)
                ? $registered->outputSchema
                : $declared->outputSchema,
            capabilities: array_values(array_unique([
                ...$registered->capabilities,
                ...$declared->capabilities,
            ])),
            approval: self::strongestApproval($registered->approval, $declared->approval),
            enabled: $registered->enabled && $declared->enabled,
            metadata: array_replace_recursive($registered->metadata, $declared->metadata),
        );
    }

    /** @param array<string, mixed> $schema */
    private static function isDefaultInputSchema(array $schema): bool
    {
        return ($schema['type'] ?? null) === 'object'
            && ($schema['properties'] ?? []) === []
            && ! isset($schema['required']);
    }

    /** @param array<string, mixed> $schema */
    private static function isDefaultOutputSchema(array $schema): bool
    {
        return $schema === ['type' => 'object'];
    }

    private static function strongestApproval(string $registered, string $declared): string
    {
        $rank = [
            SkillToolApproval::NEVER => 0,
            SkillToolApproval::CONDITIONAL => 1,
            SkillToolApproval::REQUIRED => 2,
        ];

        return ($rank[$declared] ?? 0) > ($rank[$registered] ?? 0)
            ? $declared
            : $registered;
    }
}
