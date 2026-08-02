<?php

declare(strict_types=1);

use App\Extensions\Chatbot\System\TitanAI\Contracts\TitanAIRuntimeContract;
use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;
use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIResponse;

it('defines one runtime contract for execute and stream', function (): void {
    expect(method_exists(TitanAIRuntimeContract::class, 'execute'))->toBeTrue()
        ->and(method_exists(TitanAIRuntimeContract::class, 'stream'))->toBeTrue()
        ->and(class_exists(TitanAIRequest::class))->toBeTrue()
        ->and(class_exists(TitanAIResponse::class))->toBeTrue();
});
