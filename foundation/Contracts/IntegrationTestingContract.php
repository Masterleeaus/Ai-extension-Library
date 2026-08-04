<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface IntegrationTestingContract
{
    public function createIntegrationTest(
        string $tenantId,
        string $testName,
        array $systems,
        array $scenarios
    ): string;

    public function runIntegrationTest(
        string $tenantId,
        string $testId
    ): string;

    public function getTestResults(
        string $tenantId,
        string $executionId
    ): ?array;

    public function testSystemInteraction(
        string $tenantId,
        string $system1,
        string $system2,
        array $testData
    ): array;

    public function validateDataFlow(
        string $tenantId,
        string $source,
        string $destination,
        array $data
    ): bool;

    public function testCrossSystemTransactions(
        string $tenantId,
        array $systems
    ): array;

    public function generateIntegrationReport(
        string $tenantId,
        string $executionId
    ): string;

    public function listIntegrationTests(
        string $tenantId
    ): array;
}
