<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface TestingFrameworkContract
{
    public function createTestSuite(
        string $tenantId,
        string $suiteName,
        array $tests
    ): string;

    public function runTestSuite(
        string $tenantId,
        string $suiteId
    ): string;

    public function getTestResults(
        string $tenantId,
        string $executionId
    ): ?array;

    public function addTest(
        string $tenantId,
        string $suiteId,
        array $testDefinition
    ): string;

    public function removeTest(
        string $tenantId,
        string $suiteId,
        string $testId
    ): bool;

    public function retryFailedTests(
        string $tenantId,
        string $executionId
    ): string;

    public function generateTestReport(
        string $tenantId,
        string $executionId
    ): string;

    public function listTestSuites(
        string $tenantId
    ): array;
}
