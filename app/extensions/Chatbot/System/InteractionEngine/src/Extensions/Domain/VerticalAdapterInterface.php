<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Domain\Vertical;

/**
 * VerticalAdapterInterface
 * 
 * Generalized domain adapter for any vertical (Real Estate, E-commerce, Healthcare, etc)
 * Replaces hardcoded WorkCore adapter with entity-agnostic capability dispatch.
 * 
 * Each vertical implements this once to bridge wizard answers → domain actions.
 */
interface VerticalAdapterInterface
{
    /**
     * Get the vertical identifier (e.g., 'real_estate', 'ecommerce', 'healthcare')
     */
    public function getVerticalId(): string;

    /**
     * Get the vertical schema manifest
     */
    public function getSchema(): VerticalSchemaInterface;

    /**
     * Generic create: instantiate any entity type in this vertical
     * 
     * @param string $entityType Entity name (e.g., 'property', 'product', 'patient')
     * @param array $data Entity data payload
     * @return mixed Domain model instance or result
     */
    public function create(string $entityType, array $data): mixed;

    /**
     * Generic update: modify any entity in this vertical
     * 
     * @param string $entityType Entity name
     * @param string|int $id Entity identifier
     * @param array $data Updated fields
     * @return mixed Updated domain model or result
     */
    public function update(string $entityType, string|int $id, array $data): mixed;

    /**
     * Generic delete: remove any entity from this vertical
     * 
     * @param string $entityType Entity name
     * @param string|int $id Entity identifier
     * @return bool Deletion success
     */
    public function delete(string $entityType, string|int $id): bool;

    /**
     * Fetch entity: retrieve any entity by type and ID
     * 
     * @param string $entityType Entity name
     * @param string|int $id Entity identifier
     * @return array|null Entity data or null if not found
     */
    public function fetch(string $entityType, string|int $id): ?array;

    /**
     * Query entities: search or list entities of a type
     * 
     * @param string $entityType Entity name
     * @param array $filters Filter criteria (entity-specific)
     * @param int $limit Results limit
     * @param int $offset Results offset
     * @return array Array of entity records
     */
    public function query(string $entityType, array $filters = [], int $limit = 100, int $offset = 0): array;

    /**
     * Execute a capability: dispatch wizard answers to vertical-specific business logic
     * 
     * Replaces hardcoded methods (createJob, createInvoice, etc).
     * Capability string can be: "jobs.create", "jobs.complete", "invoices.create", etc.
     * 
     * @param string $capability Capability identifier (e.g., "jobs.create")
     * @param array $payload Wizard-completed data
     * @param array $context User/tenant/device context
     * @return mixed Domain result (created entity, confirmation, etc)
     */
    public function executeCapability(string $capability, array $payload, array $context = []): mixed;

    /**
     * Validate entity data against vertical schema
     * 
     * @param string $entityType Entity name
     * @param array $data Data to validate
     * @return array Validation result: ['valid' => bool, 'errors' => array]
     */
    public function validateEntity(string $entityType, array $data): array;

    /**
     * Get relationship: resolve foreign keys or relationships
     * 
     * @param string $entityType Entity name
     * @param string|int $id Entity identifier
     * @param string $relationshipName Relationship field name
     * @return mixed Related entity or collection
     */
    public function getRelationship(string $entityType, string|int $id, string $relationshipName): mixed;

    /**
     * Apply business rules: enforce constraints after changes
     * 
     * Called after create/update to enforce vertical-specific rules
     * (e.g., recalculate commission, update inventory, send notifications)
     * 
     * @param string $operation Operation type (create|update|delete)
     * @param string $entityType Entity name
     * @param array $data Entity data (new or updated)
     * @param array $oldData Previous entity data (for update/delete)
     * @return array Resulting entity data after rule application
     */
    public function applyBusinessRules(string $operation, string $entityType, array $data, array $oldData = []): array;

    /**
     * Get audit trail: retrieve change history for an entity
     * 
     * @param string $entityType Entity name
     * @param string|int $id Entity identifier
     * @return array Array of audit log entries
     */
    public function getAuditTrail(string $entityType, string|int $id): array;

    /**
     * Resolve conflict: decide which version to keep when offline/online versions diverge
     * 
     * Return 'local', 'server', or 'merged' with resolved data
     * 
     * @param string $entityType Entity name
     * @param array $localData Offline version
     * @param array $serverData Online version
     * @param string $strategy Resolution strategy (from manifest)
     * @return array Result: ['choice' => string, 'data' => array]
     */
    public function resolveConflict(string $entityType, array $localData, array $serverData, string $strategy): array;
}
