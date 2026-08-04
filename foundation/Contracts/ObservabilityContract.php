<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface ObservabilityContract
{
    public function recordMetric(
        string $tenantId,
        string $metricName,
        float $value,
        array $tags = []
    ): void;

    public function recordLog(
        string $tenantId,
        string $level,
        string $message,
        array $context = []
    ): void;

    public function recordTrace(
        string $tenantId,
        string $traceId,
        string $spanId,
        string $operation,
        array $attributes = []
    ): void;

    public function getMetrics(
        string $tenantId,
        string $metricName,
        ?int $limit = null
    ): array;

    public function queryLogs(
        string $tenantId,
        array $filters
    ): array;

    public function getTraceContext(
        string $tenantId,
        string $traceId
    ): ?array;

    public function correlateEvents(
        string $tenantId,
        string $correlationId
    ): array;

    public function generateObservabilityReport(
        string $tenantId,
        array $parameters
    ): string;
}
