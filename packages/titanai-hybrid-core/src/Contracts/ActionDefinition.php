<?php

namespace TitanAI\Hybrid\Contracts;

interface ActionDefinition extends Registrable
{
    public function execute(array $payload, array $context = []): mixed;
    public function inputSchema(): array;
    public function outputSchema(): array;
}
