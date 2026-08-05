<?php

declare(strict_types=1);

namespace Foundation\Support;

use Foundation\Contracts\HttpApiContract;

abstract class HttpApiContractTemplate implements HttpApiContract
{
    use HttpApiSpecificationTrait;

    abstract protected static function getResourceName(): string;
    abstract protected static function getResourceSingular(): string;
    abstract protected static function getPrimaryResourceSchema(): array;

    protected static function getAdditionalEndpoints(): array { return []; }

    public static function getBasePath(): string { return '/api/' . self::getResourceName(); }
    public static function getVersion(): string { return 'v1'; }

    public static function getEndpoints(): array
    {
        $resourceName = self::getResourceName();
        $schema = self::getPrimaryResourceSchema();

        return array_merge([
            'list_' . $resourceName => self::endpoint('list_' . $resourceName, 'GET', "/api/{$resourceName}", "List {$resourceName}", null, 
                array_merge([self::parameter('tenant_id', 'query', 'Tenant ID', 'string', true)], self::paginationParameters()), null,
                [self::response('200', 'Success', self::paginatedResponse($schema)), self::errorResponse('401', 'Unauthorized', 'Auth required')]),
            'create_' . rtrim($resourceName, 's') => self::endpoint('create_' . rtrim($resourceName, 's'), 'POST', "/api/{$resourceName}", "Create", null,
                [], ['required' => ['tenant_id'], 'properties' => ['tenant_id' => self::property('string', 'Tenant ID')]],
                [self::response('201', 'Created', $schema), self::errorResponse('400', 'Bad Request', 'Invalid input')]),
            'get_' . rtrim($resourceName, 's') => self::endpoint('get_' . rtrim($resourceName, 's'), 'GET', "/api/{$resourceName}/{id}", "Get", null,
                [self::parameter('id', 'path', 'ID', 'string', true)], null,
                [self::response('200', 'Success', $schema), self::errorResponse('404', 'Not Found', 'Not found')]),
            'update_' . rtrim($resourceName, 's') => self::endpoint('update_' . rtrim($resourceName, 's'), 'PUT', "/api/{$resourceName}/{id}", "Update", null,
                [self::parameter('id', 'path', 'ID', 'string', true)], [], [self::response('200', 'Updated', $schema)]),
            'delete_' . rtrim($resourceName, 's') => self::endpoint('delete_' . rtrim($resourceName, 's'), 'DELETE', "/api/{$resourceName}/{id}", "Delete", null,
                [self::parameter('id', 'path', 'ID', 'string', true)], null, [self::response('204', 'Deleted')]),
        ], self::getAdditionalEndpoints());
    }

    public static function getRequestSchema(string $operationId): ?array
    {
        $endpoints = self::getEndpoints();
        return $endpoints[$operationId]['requestBody'] ?? null;
    }

    public static function getResponseSchema(string $operationId, string $status): ?array
    {
        $endpoints = self::getEndpoints();
        foreach (($endpoints[$operationId]['responses'] ?? []) as $response) {
            if ($response['status'] === $status) return $response['schema'] ?? null;
        }
        return null;
    }

    public static function getAuthRequirements(string $operationId): array { return self::getBearerTokenAuth(); }
    public static function getRateLimitConfig(string $operationId): array { return self::standardRateLimit(); }
    public static function getPaginationConfig(string $operationId): ?array { return strpos($operationId, 'list_') === 0 ? self::standardPagination() : null; }

    public static function getErrorResponses(string $operationId): array
    {
        $endpoints = self::getEndpoints();
        $errors = [];
        foreach (($endpoints[$operationId]['responses'] ?? []) as $response) {
            if (intval($response['status']) >= 400) $errors[$response['status']] = $response;
        }
        return $errors;
    }

    public static function getOpenApiSpecification(): array
    {
        return [
            'openapi' => '3.0.0',
            'info' => ['title' => 'API', 'version' => 'v1', 'description' => 'API Specification'],
            'servers' => [['url' => 'https://api.example.com']],
            'security' => [['bearerAuth' => []]],
            'components' => ['securitySchemes' => ['bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT']]],
        ];
    }

    public static function getRequiredHeaders(string $operationId): array { return ['Authorization']; }
    public static function getOptionalHeaders(string $operationId): array { return ['X-Request-ID', 'X-Tenant-ID']; }
}
