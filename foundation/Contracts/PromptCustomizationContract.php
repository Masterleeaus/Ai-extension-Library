<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface PromptCustomizationContract
{
    public function createPrompt(
        string $tenantId,
        string $promptName,
        string $systemPrompt,
        array $metadata = []
    ): string;

    public function getPrompt(
        string $tenantId,
        string $promptId
    ): ?array;

    public function updatePrompt(
        string $tenantId,
        string $promptId,
        string $systemPrompt
    ): bool;

    public function testPrompt(
        string $tenantId,
        string $promptId,
        string $userInput
    ): array;

    public function publishPrompt(
        string $tenantId,
        string $promptId,
        string $version
    ): bool;

    public function listPrompts(
        string $tenantId,
        ?string $category = null
    ): array;

    public function clonePrompt(
        string $tenantId,
        string $sourcePromptId,
        string $newPromptName
    ): string;

    public function deletePrompt(
        string $tenantId,
        string $promptId
    ): bool;
}
