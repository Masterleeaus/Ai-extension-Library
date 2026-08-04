<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime\Tools;

use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;

final class FieldServiceToolContextProvider
{
    public function __construct(private readonly FieldServiceToolRegistry $tools) {}

    /** @return list<array<string,mixed>> */
    public function context(TitanAIRequest $request): array
    {
        $matched = $this->tools->match($request->message);
        if ($request->toolPermissions !== []) {
            $allowed = array_fill_keys($request->toolPermissions, true);
            $matched = array_values(array_filter($matched, static fn(array $tool): bool => isset($allowed[(string)($tool['id'] ?? '')])));
        }
        if ($matched === []) return [];

        $catalogue = array_map(static fn(array $tool): array => [
            'id' => $tool['id'] ?? null,
            'operation' => $tool['operation'] ?? null,
            'permissions' => $tool['permissions'] ?? [],
            'requires_approval' => $tool['requires_approval'] ?? false,
            'capabilities' => $tool['capabilities'] ?? [],
            'input_schema' => $tool['input_schema'] ?? null,
        ], $matched);

        return [[
            'role' => 'system',
            'content' => "Relevant governed Titan Quattro tools are available. Use only tools explicitly permitted for this request. State-changing WorkCore actions remain subject to server permission and approval checks.
".json_encode($catalogue, JSON_UNESCAPED_SLASHES),
            'metadata' => ['source' => 'field-service-tool-registry', 'tools' => $catalogue],
        ]];
    }
}
