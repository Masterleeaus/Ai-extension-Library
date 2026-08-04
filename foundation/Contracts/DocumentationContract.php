<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface DocumentationContract
{
    public function generateApiDocs(
        string $tenantId,
        string $componentName
    ): string;

    public function generateArchitectureDocs(
        string $tenantId
    ): string;

    public function generateIntegrationGuide(
        string $tenantId,
        string $integrationName
    ): string;

    public function getDocumentation(
        string $tenantId,
        string $docId
    ): ?array;

    public function updateDocumentation(
        string $tenantId,
        string $docId,
        string $content
    ): bool;

    public function publishDocumentation(
        string $tenantId,
        string $docId,
        string $version
    ): bool;

    public function listDocumentation(
        string $tenantId,
        ?string $category = null
    ): array;

    public function exportDocumentation(
        string $tenantId,
        string $format
    ): string;
}
