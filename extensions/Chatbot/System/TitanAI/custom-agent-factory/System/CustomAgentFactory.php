<?php

namespace App\Extensions\CustomAgentFactory\System;

/**
 * Titan Custom Agent Factory
 * 
 * Controlled system for creating, testing, approving and managing custom Trio agents.
 * 
 * When Titan Zero detects a capability gap (no suitable existing agent):
 * 1. Search existing agents and compositions
 * 2. Ask Uno/Duo layers to define required outcome
 * 3. Generate proposed custom agent
 * 4. Select minimum required Quattro tools
 * 5. Generate permissions, schemas, tests
 * 6. Test in sandbox
 * 7. Request human approval if needed
 * 8. Register and activate
 * 9. Monitor for failures
 */
class CustomAgentFactory
{
    /**
     * Tier 2: Duo-level creation assistants (5)
     */
    public static function getCreationAssistants(): array
    {
        return [
            [
                'id' => 'duo.custom_agent_design',
                'name' => 'Custom Agent Design Assistant',
                'purpose' => 'Defines the purpose, inputs, outputs, permissions, approval requirements and limits of a missing agent',
            ],
            [
                'id' => 'duo.tool_selection',
                'name' => 'Tool Selection Assistant',
                'purpose' => 'Selects the smallest safe set of Quattro tools needed by the proposed agent',
            ],
            [
                'id' => 'duo.agent_workflow',
                'name' => 'Agent Workflow Assistant',
                'purpose' => 'Designs the steps, branches, failure states and escalation path',
            ],
            [
                'id' => 'duo.agent_testing',
                'name' => 'Agent Testing Assistant',
                'purpose' => 'Designs success, failure, permission and tenancy-isolation tests',
            ],
            [
                'id' => 'duo.agent_governance',
                'name' => 'Agent Governance Assistant',
                'purpose' => 'Checks whether the proposed autonomy level is permitted',
            ],
        ];
    }

    /**
     * Tier 3: Factory agents for custom agent creation (18)
     */
    public static function getFactoryAgents(): array
    {
        return [
            [
                'id' => 'trio.custom_factory.detect_missing',
                'name' => 'Detect Missing Agent Agent',
                'action' => 'Detect',
                'object' => 'Missing Capability',
                'purpose' => 'Identifies that no registered Trio agent satisfies the requested capability',
                'approval_required' => false,
            ],
            [
                'id' => 'trio.custom_factory.search_existing',
                'name' => 'Search Existing Capability Agent',
                'action' => 'Search',
                'object' => 'Existing Capability',
                'purpose' => 'Checks whether an existing agent or composition can perform the task',
                'approval_required' => false,
            ],
            [
                'id' => 'trio.custom_factory.draft_agent',
                'name' => 'Draft Custom Agent Agent',
                'action' => 'Draft',
                'object' => 'Custom Agent',
                'purpose' => 'Creates the proposed agent manifest and execution instructions',
                'approval_required' => false,
            ],
            [
                'id' => 'trio.custom_factory.assign_tools',
                'name' => 'Assign Custom Agent Tools Agent',
                'action' => 'Assign',
                'object' => 'Agent Tools',
                'purpose' => 'Links approved Quattro tools to the proposed agent',
                'approval_required' => false,
            ],
            [
                'id' => 'trio.custom_factory.input_schema',
                'name' => 'Generate Agent Input Schema Agent',
                'action' => 'Generate',
                'object' => 'Input Schema',
                'purpose' => 'Creates the agent\'s accepted input structure',
                'approval_required' => false,
            ],
            [
                'id' => 'trio.custom_factory.output_schema',
                'name' => 'Generate Agent Output Schema Agent',
                'action' => 'Generate',
                'object' => 'Output Schema',
                'purpose' => 'Creates the agent\'s required response structure',
                'approval_required' => false,
            ],
            [
                'id' => 'trio.custom_factory.permissions',
                'name' => 'Generate Agent Permission Agent',
                'action' => 'Generate',
                'object' => 'Permissions',
                'purpose' => 'Defines the exact permissions required by the agent',
                'approval_required' => false,
            ],
            [
                'id' => 'trio.custom_factory.approval_rules',
                'name' => 'Generate Agent Approval Rules Agent',
                'action' => 'Generate',
                'object' => 'Approval Rules',
                'purpose' => 'Defines which actions require human approval',
                'approval_required' => false,
            ],
            [
                'id' => 'trio.custom_factory.test_pack',
                'name' => 'Generate Agent Test Pack Agent',
                'action' => 'Generate',
                'object' => 'Test Pack',
                'purpose' => 'Creates automated tests for the proposed agent',
                'approval_required' => false,
            ],
            [
                'id' => 'trio.custom_factory.sandbox_test',
                'name' => 'Run Agent Sandbox Test Agent',
                'action' => 'Run',
                'object' => 'Sandbox Test',
                'purpose' => 'Tests the agent without production access',
                'approval_required' => false,
            ],
            [
                'id' => 'trio.custom_factory.isolation_test',
                'name' => 'Run Tenant Isolation Test Agent',
                'action' => 'Run',
                'object' => 'Isolation Test',
                'purpose' => 'Confirms that the agent cannot cross company boundaries',
                'approval_required' => false,
            ],
            [
                'id' => 'trio.custom_factory.permission_test',
                'name' => 'Run Permission Boundary Test Agent',
                'action' => 'Run',
                'object' => 'Permission Boundary Test',
                'purpose' => 'Confirms that the agent cannot exceed its authority',
                'approval_required' => false,
            ],
            [
                'id' => 'trio.custom_factory.submit_approval',
                'name' => 'Submit Custom Agent Approval Agent',
                'action' => 'Submit',
                'object' => 'Approval Request',
                'purpose' => 'Sends the agent package for authorised review',
                'approval_required' => true,
            ],
            [
                'id' => 'trio.custom_factory.register',
                'name' => 'Register Custom Agent Agent',
                'action' => 'Register',
                'object' => 'Custom Agent',
                'purpose' => 'Adds the approved agent to the capability registry',
                'approval_required' => true,
            ],
            [
                'id' => 'trio.custom_factory.activate',
                'name' => 'Activate Custom Agent Agent',
                'action' => 'Activate',
                'object' => 'Custom Agent',
                'purpose' => 'Enables the approved agent for permitted users',
                'approval_required' => true,
            ],
            [
                'id' => 'trio.custom_factory.monitor',
                'name' => 'Monitor Custom Agent Agent',
                'action' => 'Monitor',
                'object' => 'Custom Agent',
                'purpose' => 'Tracks performance, failures and unusual behaviour',
                'approval_required' => false,
            ],
            [
                'id' => 'trio.custom_factory.quarantine',
                'name' => 'Quarantine Custom Agent Agent',
                'action' => 'Quarantine',
                'object' => 'Custom Agent',
                'purpose' => 'Disables an unsafe or repeatedly failing agent',
                'approval_required' => true,
            ],
            [
                'id' => 'trio.custom_factory.retire',
                'name' => 'Retire Custom Agent Agent',
                'action' => 'Retire',
                'object' => 'Custom Agent',
                'purpose' => 'Removes an obsolete custom agent from active use',
                'approval_required' => true,
            ],
        ];
    }

    /**
     * Custom Agent Creation Workflow
     */
    public static function getCreationWorkflow(): array
    {
        return [
            [
                'step' => 1,
                'stage' => 'Detection',
                'agents' => ['trio.custom_factory.detect_missing', 'trio.custom_factory.search_existing'],
                'description' => 'Identify missing capability and confirm no existing solution',
            ],
            [
                'step' => 2,
                'stage' => 'Design',
                'agents' => ['trio.custom_factory.draft_agent', 'trio.custom_factory.assign_tools'],
                'description' => 'Draft agent and assign minimum required tools',
            ],
            [
                'step' => 3,
                'stage' => 'Specification',
                'agents' => ['trio.custom_factory.input_schema', 'trio.custom_factory.output_schema', 'trio.custom_factory.permissions', 'trio.custom_factory.approval_rules'],
                'description' => 'Define schemas, permissions and approval gates',
            ],
            [
                'step' => 4,
                'stage' => 'Testing',
                'agents' => ['trio.custom_factory.test_pack', 'trio.custom_factory.sandbox_test', 'trio.custom_factory.isolation_test', 'trio.custom_factory.permission_test'],
                'description' => 'Generate and run automated tests',
            ],
            [
                'step' => 5,
                'stage' => 'Approval',
                'agents' => ['trio.custom_factory.submit_approval'],
                'description' => 'Submit for human review if required',
            ],
            [
                'step' => 6,
                'stage' => 'Registration',
                'agents' => ['trio.custom_factory.register', 'trio.custom_factory.activate'],
                'description' => 'Register and activate approved agent',
            ],
            [
                'step' => 7,
                'stage' => 'Monitoring',
                'agents' => ['trio.custom_factory.monitor'],
                'description' => 'Track performance and disable if unsafe',
            ],
        ];
    }

    /**
     * Automatic Activation Criteria
     * 
     * Zero may only activate custom agents automatically when ALL are true:
     */
    public static function getAutoActivationCriteria(): array
    {
        return [
            'low_risk' => 'The task is low risk',
            'reversible' => 'The action is reversible',
            'tools_approved' => 'The tools are already approved',
            'no_sensitive_decision' => 'No sensitive or regulated decision is involved',
            'no_new_permission' => 'No new data permission is required',
            'no_money' => 'No money is transferred',
            'no_legal_notice' => 'No legal notice is issued',
            'no_safety' => 'No participant safety decision is made',
            'tests_pass' => 'All automated tests pass',
            'policy_permits' => 'The company\'s autonomy policy permits automatic activation',
        ];
    }

    /**
     * Custom Agent Naming Convention
     * 
     * Format: Titan Trio — Custom [Task] Agent
     * 
     * Internal IDs: trio.custom.[task_slug]
     */
    public static function generateCustomAgentName(string $task): string
    {
        return "Custom " . $task . " Agent";
    }

    public static function generateCustomAgentId(string $taskSlug): string
    {
        return "trio.custom." . strtolower(str_replace(' ', '_', $taskSlug));
    }

    /**
     * Custom Agent Manifest Template
     */
    public static function getAgentManifestTemplate(): array
    {
        return [
            'name' => 'Agent Name',
            'tier' => 'trio',
            'purpose' => 'Agent Purpose',
            'owning_duo_assistant' => 'Which Duo assistant owns this agent',
            'permitted_uno_relationships' => [],
            'allowed_quattro_tools' => [],
            'input_schema' => [],
            'output_schema' => [],
            'company_scope' => 'Single company or multi-tenant',
            'required_permissions' => [],
            'approval_gates' => [],
            'maximum_autonomy' => 'low|medium|high',
            'retry_rules' => [],
            'timeout_rules' => [],
            'failure_response' => 'Escalation, retry, or fail',
            'escalation_path' => 'Who to notify',
            'audit_requirements' => [],
            'test_status' => 'draft|sandbox_pass|isolated_pass|approved|active',
            'version' => '1.0.0',
            'lifecycle_status' => 'draft|proposed|approved|active|quarantined|retired',
        ];
    }

    /**
     * Example: Pool Inspection Agent
     */
    public static function getExampleCustomAgent(): array
    {
        return [
            'name' => 'Custom Pool Inspection Agent',
            'id' => 'trio.custom.pool_inspection',
            'tier' => 'trio',
            'purpose' => 'Creates and documents pool inspections with photographic evidence',
            'owning_duo_assistant' => 'Inspection Assistant',
            'permitted_uno_relationships' => ['property_owner', 'provider_manager', 'worker_manager'],
            'allowed_quattro_tools' => [
                'quattro.property.inspection',
                'quattro.field.checklist',
                'quattro.field.photo_evidence',
                'quattro.field.signature',
                'quattro.data.write',
            ],
            'input_schema' => [
                'pool_id' => 'string',
                'inspector_id' => 'string',
                'inspection_date' => 'datetime',
                'inspection_type' => 'enum[routine,pre_season,post_incident]',
            ],
            'output_schema' => [
                'inspection_id' => 'string',
                'status' => 'enum[pass,fail,needs_attention]',
                'photos' => 'array',
                'findings' => 'array',
                'evidence_pack_id' => 'string',
            ],
            'company_scope' => 'single',
            'required_permissions' => ['read:pool', 'write:inspection', 'read:write:photo'],
            'approval_gates' => ['none'],
            'maximum_autonomy' => 'medium',
            'retry_rules' => ['retry_on_timeout:true', 'retry_count:3'],
            'timeout_rules' => ['default_timeout_seconds:300'],
            'failure_response' => 'escalate_to_inspection_assistant',
            'escalation_path' => 'inspection_assistant -> compliance_assistant',
            'audit_requirements' => ['log_all_actions', 'require_inspector_signature'],
            'test_status' => 'active',
            'version' => '1.0.0',
            'lifecycle_status' => 'active',
        ];
    }

    /**
     * Get all creation assistants and factory agents
     */
    public static function getAllComponents(): array
    {
        return [
            'assistants' => self::getCreationAssistants(),
            'agents' => self::getFactoryAgents(),
            'workflow' => self::getCreationWorkflow(),
        ];
    }
}
