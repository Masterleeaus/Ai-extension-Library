<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Contracts;

use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;

interface MemoryContextProviderContract
{
    /** @return array<int,array<string,mixed>> */
    public function context(TitanAIRequest $request): array;
}
