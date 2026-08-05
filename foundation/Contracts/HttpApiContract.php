<?php

declare(strict_types=1);

namespace Foundation\Contracts;

/**
 * HTTP API Contract Interface
 *
 * Defines the contract for HTTP API endpoints provided by implementations.
 * Every implementation should document its HTTP API contract through this interface.
 */
interface HttpApiContract
{
    public static function getBasePath(): string;
    public static function getVersion(): string;
    public static function getEndpoints(): array;
    public static function getRequestSchema(string $operationId): ?array;
    public static function getResponseSchema(string $operationId, string $status): ?array;
    public static function getAuthRequirements(string $operationId): array;
    public static function getRateLimitConfig(string $operationId): array;
    public static function getPaginationConfig(string $operationId): ?array;
    public static function getErrorResponses(string $operationId): array;
    public static function getOpenApiSpecification(): array;
    public static function getRequiredHeaders(string $operationId): array;
    public static function getOptionalHeaders(string $operationId): array;
}
