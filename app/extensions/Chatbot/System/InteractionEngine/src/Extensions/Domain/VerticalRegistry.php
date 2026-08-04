<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Domain\Vertical;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * VerticalRegistry
 * 
 * Central registry for all vertical adapters and schemas.
 * Loads manifests, instantiates adapters, manages vertical context.
 * 
 * Replaces the hardcoded WorkCoreAdapter with a dynamic, multi-vertical system.
 */
final class VerticalRegistry
{
    /** @var array<string, VerticalAdapterInterface> */
    private array $adapters = [];

    /** @var array<string, VerticalSchemaInterface> */
    private array $schemas = [];

    /** @var array<string, array> */
    private array $manifests = [];

    /** @var string Currently active vertical */
    private string $activeVertical;

    /**
     * @param array<string, array> $manifestConfigs Vertical manifest configs (from YAML/JSON)
     * @param string $activeVertical Which vertical is active (from .env or default)
     */
    public function __construct(
        array $manifestConfigs = [],
        string $activeVertical = 'workcore'
    ) {
        $this->manifests = $manifestConfigs;
        $this->activeVertical = $activeVertical;
    }

    /**
     * Register a vertical adapter
     * 
     * @param string $verticalId Vertical identifier
     * @param VerticalAdapterInterface $adapter Adapter instance
     * @return void
     */
    public function registerAdapter(string $verticalId, VerticalAdapterInterface $adapter): void
    {
        $this->adapters[$verticalId] = $adapter;
        Log::debug("Registered adapter for vertical: {$verticalId}");
    }

    /**
     * Register a vertical schema
     * 
     * @param string $verticalId Vertical identifier
     * @param VerticalSchemaInterface $schema Schema instance
     * @return void
     */
    public function registerSchema(string $verticalId, VerticalSchemaInterface $schema): void
    {
        $this->schemas[$verticalId] = $schema;
        Log::debug("Registered schema for vertical: {$verticalId}");
    }

    /**
     * Get adapter for a vertical
     * 
     * @param string|null $verticalId If null, uses active vertical
     * @return VerticalAdapterInterface
     * @throws RuntimeException If adapter not found
     */
    public function getAdapter(?string $verticalId = null): VerticalAdapterInterface
    {
        $vertical = $verticalId ?? $this->activeVertical;
        
        if (!isset($this->adapters[$vertical])) {
            throw new RuntimeException("No adapter registered for vertical: {$vertical}");
        }

        return $this->adapters[$vertical];
    }

    /**
     * Get schema for a vertical
     * 
     * @param string|null $verticalId If null, uses active vertical
     * @return VerticalSchemaInterface
     * @throws RuntimeException If schema not found
     */
    public function getSchema(?string $verticalId = null): VerticalSchemaInterface
    {
        $vertical = $verticalId ?? $this->activeVertical;
        
        if (!isset($this->schemas[$vertical])) {
            throw new RuntimeException("No schema registered for vertical: {$vertical}");
        }

        return $this->schemas[$vertical];
    }

    /**
     * Get all registered verticals
     * 
     * @return array<string> Vertical IDs
     */
    public function getVerticals(): array
    {
        return array_keys($this->adapters);
    }

    /**
     * Check if vertical is registered
     * 
     * @param string $verticalId Vertical identifier
     * @return bool
     */
    public function hasVertical(string $verticalId): bool
    {
        return isset($this->adapters[$verticalId]) && isset($this->schemas[$verticalId]);
    }

    /**
     * Set the active vertical
     * 
     * @param string $verticalId Vertical identifier
     * @return void
     * @throws RuntimeException If vertical not registered
     */
    public function setActive(string $verticalId): void
    {
        if (!$this->hasVertical($verticalId)) {
            throw new RuntimeException("Cannot activate unregistered vertical: {$verticalId}");
        }
        
        $this->activeVertical = $verticalId;
        Log::info("Switched active vertical to: {$verticalId}");
    }

    /**
     * Get active vertical ID
     * 
     * @return string
     */
    public function getActive(): string
    {
        return $this->activeVertical;
    }

    /**
     * Get manifest for a vertical
     * 
     * @param string|null $verticalId If null, uses active vertical
     * @return array|null Manifest configuration
     */
    public function getManifest(?string $verticalId = null): ?array
    {
        $vertical = $verticalId ?? $this->activeVertical;
        return $this->manifests[$vertical] ?? null;
    }

    /**
     * Dispatch wizard answers to the active vertical's adapter
     * 
     * This is the core method that replaces hardcoded WorkCore methods.
     * Wizard system calls this with a capability string and wizard data.
     * 
     * @param string $capability Capability string (e.g., "jobs.create")
     * @param array $payload Completed wizard data
     * @param array $context User/tenant/device context
     * @return mixed Domain result
     * @throws RuntimeException If capability not supported
     */
    public function dispatchCapability(string $capability, array $payload, array $context = []): mixed
    {
        $adapter = $this->getAdapter();
        
        if (!method_exists($adapter, 'executeCapability')) {
            throw new RuntimeException("Adapter does not support executeCapability: " . get_class($adapter));
        }

        Log::debug("Dispatching capability to vertical adapter", [
            'vertical' => $this->activeVertical,
            'capability' => $capability,
        ]);

        return $adapter->executeCapability($capability, $payload, $context);
    }

    /**
     * Get all entities across all verticals
     * 
     * Useful for admin dashboards or cross-vertical queries
     * 
     * @return array<string, array> ['vertical_id' => ['entities' => [...]]]
     */
    public function getAllEntitiesAcrossVerticals(): array
    {
        $result = [];
        
        foreach ($this->getVerticals() as $vertical) {
            $schema = $this->getSchema($vertical);
            $result[$vertical] = [
                'vertical_name' => $schema->getName(),
                'entities' => $schema->getEntities(),
            ];
        }

        return $result;
    }

    /**
     * Validate that a vertical configuration is complete
     * 
     * @param string $verticalId Vertical to validate
     * @return array Validation result: ['valid' => bool, 'errors' => array]
     */
    public function validateVertical(string $verticalId): array
    {
        $errors = [];

        if (!isset($this->adapters[$verticalId])) {
            $errors[] = "Adapter not registered";
        }

        if (!isset($this->schemas[$verticalId])) {
            $errors[] = "Schema not registered";
        }

        if (!isset($this->manifests[$verticalId])) {
            $errors[] = "Manifest not registered";
        }

        $adapter = $this->adapters[$verticalId] ?? null;
        if ($adapter && !($adapter instanceof VerticalAdapterInterface)) {
            $errors[] = "Adapter does not implement VerticalAdapterInterface";
        }

        $schema = $this->schemas[$verticalId] ?? null;
        if ($schema && !($schema instanceof VerticalSchemaInterface)) {
            $errors[] = "Schema does not implement VerticalSchemaInterface";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'vertical_id' => $verticalId,
        ];
    }
}
