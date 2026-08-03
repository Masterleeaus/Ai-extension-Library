<?php

namespace TitanAI\Hybrid\Contracts;

interface ToolDefinition extends Registrable
{
    public function execute(array $parameters): mixed;
    public function parameterSchema(): array;
    public function category(): string;
}
