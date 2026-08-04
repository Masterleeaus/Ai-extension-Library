<?php

return [
    'enabled' => env('TITAN_AI_GOVERNANCE_ENABLED', true),
    'workcore_authoritative' => true,
    'minimum_confidence' => 70,
    'reviewers' => [
        ['model' => env('TITAN_COUNCIL_PRIMARY_REVIEWER', 'gpt-5.1'), 'role' => 'fact_verifier'],
        ['model' => env('TITAN_COUNCIL_SECONDARY_REVIEWER', 'gpt-5.5'), 'role' => 'risk_auditor'],
    ],
    'risk' => [
        'green' => ['reviewers' => 0, 'human_approval' => false],
        'amber' => ['reviewers' => 1, 'human_approval' => false],
        'red' => ['reviewers' => 2, 'human_approval' => false],
        'critical' => ['reviewers' => 2, 'human_approval' => true],
    ],
    'memory' => [
        'reflection_is_authoritative' => false,
        'default_ttl_seconds' => 2592000,
        'personal_data_encrypted' => true,
    ],
];
