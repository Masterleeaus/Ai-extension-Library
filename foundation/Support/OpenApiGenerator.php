<?php

declare(strict_types=1);

namespace Foundation\Support;

use Foundation\Contracts\HttpApiContract;

class OpenApiGenerator
{
    private array $specs = [];

    public function register(string $contractClass): self
    {
        if (!is_subclass_of($contractClass, HttpApiContract::class)) {
            throw new \InvalidArgumentException("Contract must implement HttpApiContract");
        }
        $this->specs[$contractClass] = null;
        return $this;
    }

    public function generateForContract(string $contractClass): array
    {
        if (!is_subclass_of($contractClass, HttpApiContract::class)) {
            throw new \InvalidArgumentException("Contract must implement HttpApiContract");
        }

        $spec = $contractClass::getOpenApiSpecification();
        $paths = [];
        $endpoints = $contractClass::getEndpoints();

        foreach ($endpoints as $operationId => $endpoint) {
            $path = $endpoint['path'];
            $method = strtolower($endpoint['method']);
            if (!isset($paths[$path])) $paths[$path] = [];

            $pathItem = [
                'operationId' => $operationId,
                'summary' => $endpoint['summary'] ?? '',
                'parameters' => $endpoint['parameters'] ?? [],
            ];

            if (!empty($endpoint['requestBody'])) {
                $pathItem['requestBody'] = [
                    'required' => true,
                    'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => $endpoint['requestBody']['properties'] ?? []]]],
                ];
            }

            $responses = [];
            foreach ($endpoint['responses'] ?? [] as $response) {
                $statusCode = $response['status'];
                $responses[$statusCode] = ['description' => $response['description']];
                if (isset($response['schema'])) {
                    $responses[$statusCode]['content'] = ['application/json' => ['schema' => $response['schema']]];
                }
            }
            $pathItem['responses'] = $responses ?: ['200' => ['description' => 'OK']];
            $paths[$path][$method] = $pathItem;
        }

        $spec['paths'] = $paths;
        return $spec;
    }

    public function generateAll(): array
    {
        $allSpecs = [];
        foreach (array_keys($this->specs) as $contractClass) {
            $allSpecs[$contractClass] = $this->generateForContract($contractClass);
        }
        return $allSpecs;
    }

    public static function toJson(array $spec, int $flags = JSON_PRETTY_PRINT): string
    {
        return json_encode($spec, $flags | JSON_THROW_ON_ERROR);
    }

    public static function toYaml(array $spec): string
    {
        return self::arrayToYaml($spec);
    }

    private static function arrayToYaml(array $array, int $indent = 0): string
    {
        $yaml = '';
        $indentStr = str_repeat('  ', $indent);

        foreach ($array as $key => $value) {
            $yaml .= "$indentStr" . self::escapeYamlKey($key) . ": ";

            if (is_array($value)) {
                $yaml .= empty($value) ? "{}\n" : "\n" . self::arrayToYaml($value, $indent + 1);
            } elseif (is_bool($value)) {
                $yaml .= ($value ? 'true' : 'false') . "\n";
            } elseif (is_null($value)) {
                $yaml .= "null\n";
            } else {
                $yaml .= self::escapeYamlValue($value) . "\n";
            }
        }

        return $yaml;
    }

    private static function escapeYamlKey(string $key): string
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $key) ? $key : '"' . addcslashes($key, '"\\') . '"';
    }

    private static function escapeYamlValue($value): string
    {
        if (is_string($value) && (empty($value) || preg_match('/[:#{}[\],&*!|\'">%@`]/', $value))) {
            return '"' . addcslashes($value, '"\\') . '"';
        }
        return (string)$value;
    }
}
