<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Contracts;

use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;
use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIResponse;

interface TitanAIRuntimeContract
{
    public function execute(TitanAIRequest $request): TitanAIResponse;

    /** @param callable(array<string,mixed>):void $emit */
    public function stream(TitanAIRequest $request, callable $emit): TitanAIResponse;
}
