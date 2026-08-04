<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime\Skills;

use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;

final class FieldServiceSkillContextProvider
{
    public function __construct(private readonly FieldServiceSkillRegistry $registry) {}

    /** @return list<array<string,mixed>> */
    public function context(TitanAIRequest $request): array
    {
        return array_map(static fn (array $skill): array => [
            'role' => 'system',
            'name' => 'field-service-skill:'.$skill['slug'],
            'content' => "Use this governed skill when relevant. Do not bypass WorkCore authority or approval rules.\n\n".$skill['instructions'],
            'metadata' => [
                'skill_slug' => $skill['slug'],
                'skill_version' => $skill['version'],
                'capabilities' => $skill['capabilities'],
                'tools' => $skill['tools'],
                'sha256' => $skill['sha256'],
            ],
        ], $this->registry->match($request->message, $request->template));
    }
}
