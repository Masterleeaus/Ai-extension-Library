<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface TemplateManagementContract
{
    public function createTemplate(
        string $tenantId,
        string $templateName,
        string $templateType,
        string $content,
        array $metadata = []
    ): string;

    public function getTemplate(
        string $tenantId,
        string $templateId
    ): ?array;

    public function updateTemplate(
        string $tenantId,
        string $templateId,
        string $content
    ): bool;

    public function renderTemplate(
        string $tenantId,
        string $templateId,
        array $variables
    ): string;

    public function listTemplates(
        string $tenantId,
        string $templateType
    ): array;

    public function cloneTemplate(
        string $tenantId,
        string $sourceTemplateId,
        string $newTemplateName
    ): string;

    public function previewTemplate(
        string $tenantId,
        string $templateId,
        array $sampleData
    ): string;

    public function deleteTemplate(
        string $tenantId,
        string $templateId
    ): bool;
}
