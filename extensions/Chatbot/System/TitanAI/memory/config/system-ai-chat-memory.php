<?php

return [
    'enabled' => true,
    'types' => ['episodic', 'semantic', 'procedural', 'preference', 'relationship', 'operational'],
    'scopes' => ['user', 'company', 'team', 'customer', 'property', 'job', 'conversation', 'worker'],
    'default_recall_limit' => 20,
    'max_recall_limit' => 100,
    'default_retention_days' => 365,
    'sensitive_memory_requires_consent' => true,
    'automatic_decay' => true,
    'decay_factor' => 0.98,
    'store_raw_conversation' => false,
    'tenant_isolation' => true,
    'allow_user_export' => true,
    'allow_selective_forgetting' => true,
];
