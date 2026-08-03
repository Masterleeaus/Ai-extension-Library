<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Contracts;

interface WorkCoreGateway
{
    public function read(string $domain, string $operation, array $payload): array;
    public function execute(string $domain, string $operation, array $payload): array;
}
