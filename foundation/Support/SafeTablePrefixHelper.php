<?php

declare(strict_types=1);

namespace Foundation\Support;

class SafeTablePrefixHelper
{
    private const APPROVED_PREFIXES = [
        'extension_lifecycle_' => true,
        'prompt_customization_' => true,
        'rate_limiting_' => true,
        'research_engine_' => true,
        'shadow_validation_' => true,
        'skill_runtime_' => true,
        'template_management_' => true,
        'tool_execution_' => true,
        'webhook_security_' => true,
        'work_core_business_network_' => true,
        'work_core_commercial_' => true,
        'work_core_foundation_' => true,
        'work_core_operations_' => true,
        'work_core_property_' => true,
        'work_core_workforce_' => true,
        'workflow_engine_' => true,
        'access_control_' => true,
        'audit_trail_' => true,
        'behavior_configuration_' => true,
        'booking_engine_' => true,
        'booking_migration_' => true,
        'branding_theming_' => true,
        'commerce_contract_' => true,
        'connector_migration_' => true,
        'connector_runtime_' => true,
        'customer_identity_' => true,
        'ecommerce_integration_' => true,
        'feature_flag_' => true,
        'forms_builder_' => true,
        'governance_' => true,
        'host_integration_' => true,
        'knowledge_engine_' => true,
        'localization_' => true,
        'media_quarantine_' => true,
        'migration_' => true,
        'authorization_policy_' => true,
        'data_encryption_' => true,
        'file_ownership_' => true,
        'secure_remote_fetcher_' => true,
        'voice_engine_' => true,
        'credential_vault_' => true,
        'event_envelope_' => true,
        'tenant_context_' => true,
    ];

    public static function isApprovedPrefix(string $prefix): bool
    {
        return isset(self::APPROVED_PREFIXES[$prefix]);
    }

    public static function getApprovedPrefix(string $prefix): string
    {
        if (!self::isApprovedPrefix($prefix)) {
            throw new \InvalidArgumentException(
                sprintf('Table prefix "%s" is not in the approved whitelist', $prefix)
            );
        }
        return $prefix;
    }

    public static function buildTableName(string $prefix, string $tableName): string
    {
        if (!self::isApprovedPrefix($prefix)) {
            throw new \InvalidArgumentException(
                sprintf('Table prefix "%s" is not in the approved whitelist', $prefix)
            );
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
            throw new \InvalidArgumentException(
                sprintf('Table name "%s" contains invalid characters', $tableName)
            );
        }

        return $prefix . $tableName;
    }

    public static function getApprovedPrefixes(): array
    {
        return self::APPROVED_PREFIXES;
    }
}
