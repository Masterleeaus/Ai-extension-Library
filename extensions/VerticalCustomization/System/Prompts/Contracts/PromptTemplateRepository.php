<?php

declare(strict_types=1);

namespace App\Extensions\VerticalCustomization\System\Prompts\Contracts;

interface PromptTemplateRepository
{
    /**
     * Create a new prompt template for a vertical.
     */
    public function create(int $tenantId, string $verticalId, string $templateName, string $promptText, array $variables = []): string;

    /**
     * Retrieve prompt template by ID.
     */
    public function getById(int $tenantId, string $templateId): ?array;

    /**
     * List all prompt templates for a vertical.
     */
    public function listForVertical(int $tenantId, string $verticalId): array;

    /**
     * Update existing prompt template (creates new version).
     */
    public function update(int $tenantId, string $templateId, string $promptText): string;

    /**
     * Publish prompt template version.
     */
    public function publish(int $tenantId, string $templateId, int $versionId): void;

    /**
     * Rollback to previous version.
     */
    public function rollback(int $tenantId, string $templateId, int $versionId): void;
}
