<?php

declare(strict_types=1);

namespace Foundation\Tools\Services;

use Foundation\Tools\GovernedToolDefinition;

/**
 * Service for registering and retrieving tool definitions.
 */
interface ToolRegistryService
{
    /**
     * Register a tool definition.
     *
     * @param GovernedToolDefinition $definition The tool definition
     */
    public function register(GovernedToolDefinition $definition): void;

    /**
     * Get a tool definition by name.
     *
     * @param string $name Tool name
     * @return ?GovernedToolDefinition The definition or null if not found
     */
    public function get(string $name): ?GovernedToolDefinition;

    /**
     * Check if a tool is registered.
     *
     * @param string $name Tool name
     * @return bool True if registered
     */
    public function has(string $name): bool;
}
