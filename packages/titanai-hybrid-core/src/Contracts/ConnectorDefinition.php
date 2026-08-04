<?php

namespace TitanAI\Hybrid\Contracts;

/**
 * ConnectorDefinition Contract
 * 
 * Defines connectors that bridge external services (Telegram, MagicAI, etc.)
 */
interface ConnectorDefinition extends Registrable
{
    /**
     * Check if connector is properly configured.
     * 
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Get connector configuration schema.
     * 
     * @return array
     */
    public function configSchema(): array;

    /**
     * Send data through the connector.
     * 
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function send(array $data): mixed;

    /**
     * Receive/fetch data from the connector.
     * 
     * @return array
     * @throws \Exception
     */
    public function receive(): array;
}
