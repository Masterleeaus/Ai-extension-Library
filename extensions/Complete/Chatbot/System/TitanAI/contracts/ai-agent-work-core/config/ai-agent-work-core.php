<?php

return [
    'enabled' => true,
    
    'modules' => [
        'crm' => true,
        'projects' => true,
        'jobs' => true,
        'invoicing' => true,
        'time_tracking' => true,
        'field_ops' => true,
        'inventory' => true,
    ],

    'agent_permissions' => [
        'read' => true,
        'create' => true,
        'update' => true,
        'delete' => false, // Agents can't delete, only mark complete
    ],

    'audit_logging' => true,
];
