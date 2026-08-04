<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface PerformanceTestingContract
{
    public function createPerformanceTest(
        string $tenantId,
        string $testName,
        array $testConfig
    ): string;

    public function runLoadTest(
        string $tenantId,
        string $testId,
        int $concurrentUsers,
        int $durationSeconds
    ): string;

    public function getPerformanceMetrics(
        string $tenantId,
        string $executionId
    ): ?array;

    public function testResponseTime(
        string $tenantId,
        string $endpoint,
        int $iterations
    ): array;

    public function testThroughput(
        string $tenantId,
        string $operation,
        int $duration
    ): array;

    public function identifyBottlenecks(
        string $tenantId,
        string $executionId
    ): array;

    public function generatePerformanceReport(
        string $tenantId,
        string $executionId
    ): string;

    public function comparePerformance(
        string $tenantId,
        string $executionId1,
        string $executionId2
    ): array;
}
