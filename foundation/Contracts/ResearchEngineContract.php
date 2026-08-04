<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface ResearchEngineContract
{
    public function startResearch(
        string $tenantId,
        string $topic,
        array $parameters = []
    ): string;

    public function getResearchStatus(
        string $tenantId,
        string $researchId
    ): ?array;

    public function getFindings(
        string $tenantId,
        string $researchId
    ): array;

    public function getCitations(
        string $tenantId,
        string $researchId,
        ?string $findingId = null
    ): array;

    public function pauseResearch(
        string $tenantId,
        string $researchId
    ): bool;

    public function resumeResearch(
        string $tenantId,
        string $researchId
    ): bool;

    public function cancelResearch(
        string $tenantId,
        string $researchId
    ): bool;

    public function exportFindings(
        string $tenantId,
        string $researchId,
        string $format
    ): string;

    public function verifyFinding(
        string $tenantId,
        string $researchId,
        string $findingId,
        array $evidence
    ): bool;
}
