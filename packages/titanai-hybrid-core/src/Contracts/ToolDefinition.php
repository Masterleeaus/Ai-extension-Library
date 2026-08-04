<?php

namespace TitanAI\Hybrid\Contracts;

/**
 * ToolDefinition Contract
 * 
 * Defines tools/utilities that enhance AI capabilities.
 */
interface ToolDefinition extends Registrable
{
    /**
     * Execute the tool with parameters.
     * 
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public function execute(array $parameters): mixed;

    /**
     * Get tool parameter schema for Claude/AI.
     * 
     * @return array
     */
    public function parameterSchema(): array;

    /**
     * Get tool category (e.g., 'data', 'integration', 'utility').
     * 
     * @return string
     */
    public function category(): string;
}
