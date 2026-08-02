<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Contracts;

use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;

interface GeneralConversationEngineContract
{
    /** @return array<string,mixed> */
    public function complete(TitanAIRequest $request, array $runtimeContext = []): array;
}
