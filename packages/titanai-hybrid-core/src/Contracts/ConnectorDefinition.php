<?php

namespace TitanAI\Hybrid\Contracts;

interface ConnectorDefinition extends Registrable
{
    public function isConfigured(): bool;
    public function configSchema(): array;
    public function send(array $data): mixed;
    public function receive(): array;
}
