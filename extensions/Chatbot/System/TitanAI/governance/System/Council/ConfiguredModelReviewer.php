<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Council;

use App\Extensions\TitanAIGovernance\System\Contracts\ModelReviewer;
use RuntimeException;

final readonly class ConfiguredModelReviewer implements ModelReviewer
{
    public function __construct(public string $model, public string $role) {}

    public function review(array $case): array
    {
        if (! app()->bound('TitanModelRouter')) {
            throw new RuntimeException('TitanModelRouter is not bound. Council review cannot run.');
        }

        $prompt = [
            'instruction' => 'Independently verify the proposed operational action. Do not assume the planner is correct. Check only supplied facts, constraints and evidence. Return strict JSON.',
            'role' => $this->role,
            'case' => $case,
            'response_schema' => [
                'decision' => 'approve|reject|human_review|insufficient_evidence',
                'confidence' => 'integer 0-100',
                'findings' => ['string'],
                'evidence' => ['string'],
            ],
        ];

        $response = app('TitanModelRouter')->complete([
            'model' => $this->model,
            'messages' => [['role' => 'user', 'content' => json_encode($prompt, JSON_THROW_ON_ERROR)]],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0,
        ]);

        $decoded = is_array($response) ? $response : json_decode((string) $response, true, 512, JSON_THROW_ON_ERROR);
        return [
            'decision' => (string) ($decoded['decision'] ?? 'insufficient_evidence'),
            'confidence' => max(0, min(100, (int) ($decoded['confidence'] ?? 0))),
            'findings' => array_values((array) ($decoded['findings'] ?? [])),
            'evidence' => array_values((array) ($decoded['evidence'] ?? [])),
            'model' => $this->model,
            'role' => $this->role,
        ];
    }
}
