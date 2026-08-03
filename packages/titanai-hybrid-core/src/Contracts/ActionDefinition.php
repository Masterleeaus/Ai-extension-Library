<?php

namespace TitanAI\Hybrid\Contracts;

/**
 * ActionDefinition Contract
 * 
 * Defines actions that can be invoked by AIAgent and other extensions.
 */
interface ActionDefinition extends Registrable
{
    /**
     * Execute the action with the given payload.
     * 
     * @param array $payload
     * @param array $context [optional] userId, workflowId, etc.
     * @return mixed
     * @throws \Exception
     */
    public function execute(array $payload, array $context = []): mixed;

    /**
     * Get action input schema (for validation).
     * 
     * @return array
     */
    public function inputSchema(): array;

    /**
     * Get action output schema.
     * 
     * @return array
     */
    public function outputSchema(): array;
}
