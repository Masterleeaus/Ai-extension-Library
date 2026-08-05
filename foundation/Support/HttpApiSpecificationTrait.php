<?php

declare(strict_types=1);

namespace Foundation\Support;

trait HttpApiSpecificationTrait
{
    protected static function endpoint($operationId, $method, $path, $summary, $description = null, $parameters = [], $requestBody = null, $responses = [])
    {
        return [
            'operationId' => $operationId,
            'method' => strtoupper($method),
            'path' => $path,
            'summary' => $summary,
            'description' => $description,
            'parameters' => $parameters,
            'requestBody' => $requestBody,
            'responses' => $responses,
        ];
    }

    protected static function parameter($name, $in, $description, $schema = 'string', $required = false, $default = null)
    {
        return [
            'name' => $name,
            'in' => $in,
            'description' => $description,
            'required' => $required,
            'schema' => ['type' => $schema, 'default' => $default],
        ];
    }

    protected static function response($statusCode, $description, $schema = null)
    {
        return ['status' => $statusCode, 'description' => $description, 'schema' => $schema];
    }

    protected static function property($type, $description, $default = null, $enum = [], $properties = [], $minLength = null, $maxLength = null)
    {
        $prop = ['type' => $type, 'description' => $description];
        if ($default !== null) $prop['default'] = $default;
        if (!empty($enum)) $prop['enum'] = $enum;
        if (!empty($properties)) $prop['properties'] = $properties;
        if ($minLength !== null) $prop['minLength'] = $minLength;
        if ($maxLength !== null) $prop['maxLength'] = $maxLength;
        return $prop;
    }

    protected static function errorResponse($statusCode, $title, $description)
    {
        return [
            'status' => $statusCode,
            'description' => $description,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'status' => ['type' => 'string', 'example' => 'error'],
                    'code' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                    'error_id' => ['type' => 'string'],
                    'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
        ];
    }

    protected static function paginationParameters()
    {
        return [
            self::parameter('page', 'query', 'Page number', 'integer', false, 1),
            self::parameter('per_page', 'query', 'Items per page', 'integer', false, 20),
        ];
    }

    protected static function paginatedResponse($itemSchema)
    {
        return [
            'type' => 'object',
            'properties' => [
                'data' => ['type' => 'array', 'items' => $itemSchema],
                'pagination' => ['type' => 'object', 'properties' => ['total' => ['type' => 'integer'], 'page' => ['type' => 'integer']]],
            ],
        ];
    }

    protected static function getBearerTokenAuth()
    {
        return ['type' => 'bearer', 'scheme' => 'bearer', 'bearerFormat' => 'JWT', 'required' => true];
    }

    protected static function getApiKeyAuth()
    {
        return ['type' => 'api_key', 'name' => 'X-API-Key', 'in' => 'header', 'required' => true];
    }

    protected static function standardRateLimit($requestsPerMinute = 60, $requestsPerHour = 1000, $burstSize = 10)
    {
        return ['requests_per_minute' => $requestsPerMinute, 'requests_per_hour' => $requestsPerHour, 'burst_size' => $burstSize];
    }

    protected static function standardPagination($limitParam = 'per_page', $offsetParam = 'page', $defaultLimit = 20, $maxLimit = 100)
    {
        return ['limit_param' => $limitParam, 'offset_param' => $offsetParam, 'default_limit' => $defaultLimit, 'max_limit' => $maxLimit];
    }
}
