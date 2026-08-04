<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Contracts;

use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;

interface AssistantDeploymentResolverContract
{
    /** @return array<string,mixed> */
    public function resolve(TitanAIRequest $request): array;
}
