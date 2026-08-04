<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Domain\Vertical;

/**
 * VerticalSchemaInterface
 * 
 * Represents the entity model and workflow definitions for a vertical.
 * Loaded from YAML/JSON manifests, defines what entities exist and how they relate.
 */
interface VerticalSchemaInterface
{
    /**
     * Get vertical identifier
     */
    public function getVerticalId(): string;

    /**
     * Get vertical name (human-readable)
     */
    public function getName(): string;

    /**
     * Get all entity definitions
     * 
     * @return array Array of EntityDefinition objects or arrays
     */
    public function getEntities(): array;

    /**
     * Get a single entity definition
     * 
     * @param string $entityType Entity name
     * @return EntityDefinitionInterface Entity definition or null
     */
    public function getEntity(string $entityType): ?EntityDefinitionInterface;

    /**
     * Get all wizard definitions for this vertical
     * 
     * @return array Array of WizardDefinition objects
     */
    public function getWizards(): array;

    /**
     * Get a single wizard definition
     * 
     * @param string $wizardId Wizard identifier
     * @return array|null Wizard definition
     */
    public function getWizard(string $wizardId): ?array;

    /**
     * Get business rules (entity lifecycle hooks)
     * 
     * @return array Array of rule definitions
     */
    public function getBusinessRules(): array;

    /**
     * Get workflows and status transitions
     * 
     * @return array Array of workflow definitions
     */
    public function getWorkflows(): array;

    /**
     * Get relationships between entities
     * 
     * @return array Relationship definitions
     */
    public function getRelationships(): array;

    /**
     * Check if entity supports offline mode
     * 
     * @param string $entityType Entity name
     * @return bool
     */
    public function isOfflineCapable(string $entityType): bool;

    /**
     * Get conflict resolution strategy for entity
     * 
     * @param string $entityType Entity name
     * @return string Strategy: 'last_write_wins', 'manual', 'server_wins', 'local_wins'
     */
    public function getConflictStrategy(string $entityType): string;

    /**
     * Get audit requirements for entity
     * 
     * @param string $entityType Entity name
     * @return array Audit requirements
     */
    public function getAuditRequirements(string $entityType): array;
}

/**
 * EntityDefinitionInterface
 * 
 * Defines a single entity type in the vertical schema
 */
interface EntityDefinitionInterface
{
    public function getName(): string;
    public function getTableName(): string;
    public function getFields(): array;
    public function getPrimaryKey(): string;
    public function getIndexes(): array;
    public function getRelationships(): array;
    public function getValidationRules(): array;
    public function isAudited(): bool;
    public function requiresApproval(): bool;
}
