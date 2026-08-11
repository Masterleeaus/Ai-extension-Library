<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\Api;

use App\Extensions\Migration\System\Connectors\AbstractSourceConnector;
use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class OpenApiConnector extends AbstractSourceConnector
{
    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'openapi',
            name: 'OpenAPI-described API',
            connectorClass: self::class,
            category: 'api',
            authenticationTypes: ['bearer_token', 'api_key', 'headers'],
            capabilities: ['connection_test', 'openapi_discovery', 'get_operations_only', 'paged_stream', 'incremental_parameter'],
            supportsDiscovery: true,
            supportsIncrementalSync: true,
            readOnly: true,
            streamingMode: 'paged',
        );
    }

    public function testConnection(array $configuration): array
    {
        try {
            $spec = $this->loadSpec($configuration);

            return [
                'successful' => isset($spec['openapi']) || isset($spec['swagger']),
                'connector' => 'openapi',
                'configuration' => $this->redactConfiguration($configuration),
            ];
        } catch (\Throwable) {
            return [
                'successful' => false,
                'connector' => 'openapi',
                'message' => 'OpenAPI document could not be loaded.',
                'configuration' => $this->redactConfiguration($configuration),
            ];
        }
    }

    public function discover(array $configuration): array
    {
        $spec = $this->loadSpec($configuration);
        $entities = [];

        foreach (($spec['paths'] ?? []) as $path => $pathItem) {
            if (! is_array($pathItem) || ! is_array($pathItem['get'] ?? null)) {
                continue;
            }
            $operation = $pathItem['get'];
            $name = trim((string) ($operation['operationId'] ?? ''));
            if ($name === '') {
                $name = trim(str_replace(['{', '}', '/'], ['', '', '_'], (string) $path), '_') ?: 'root';
            }
            $schema = $this->responseSchema($spec, $operation);
            $fields = $this->fieldsFromSchema($spec, $schema);
            $fieldNames = array_column($fields, 'name');
            $entities[] = [
                'name' => $name,
                'resource_path' => (string) $path,
                'record_count' => null,
                'record_count_known' => false,
                'fields' => $fields,
                'keys' => $this->keyHints($fieldNames),
                'relationships' => $this->relationshipHints($fieldNames),
                'incremental_fields' => array_values(array_intersect(['updated_at', 'modified_at', 'created_at', 'version'], $fieldNames)),
                'attachment_fields' => array_values(array_filter($fieldNames, static fn (string $field): bool => preg_match('/(?:file|attachment|image|photo|document|media|url|path)/i', $field) === 1)),
                'samples' => [],
                'parameters' => $operation['parameters'] ?? [],
            ];
        }

        return $this->discoveryResult($entities, [
            'openapi_version' => $spec['openapi'] ?? $spec['swagger'] ?? null,
            'base_url' => $this->baseUrl($configuration, $spec),
        ]);
    }

    public function stream(array $entityPlan, ?array $checkpoint = null): iterable
    {
        $configuration = $this->configurationFromPlan($entityPlan);
        $spec = $this->loadSpec($configuration);
        $source = is_array($entityPlan['source'] ?? null) ? $entityPlan['source'] : [];
        $path = (string) ($source['path'] ?? $source['resource_path'] ?? '');
        if ($path === '') {
            throw new InvalidArgumentException('OpenAPI extraction requires source.path.');
        }

        $pathParams = is_array($source['path_params'] ?? null) ? $source['path_params'] : [];
        foreach ($pathParams as $key => $value) {
            $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
        }
        if (preg_match('/\{[^}]+\}/', $path) === 1) {
            throw new InvalidArgumentException('OpenAPI source path has unresolved path parameters.');
        }

        if (! is_array($spec['paths'][$source['path'] ?? $source['resource_path'] ?? $path]['get'] ?? null)
            && ! is_array($spec['paths'][$path]['get'] ?? null)) {
            throw new InvalidArgumentException('OpenAPI extraction is limited to documented GET operations.');
        }

        $restConfiguration = array_merge($configuration, ['base_url' => $this->baseUrl($configuration, $spec)]);
        $restPlan = [
            'configuration' => $restConfiguration,
            'source' => array_merge($source, ['path' => $path]),
        ];

        return (new RestConnector())->stream($restPlan, $checkpoint);
    }

    public function readIncremental(array $entityPlan, array $checkpoint): iterable
    {
        if (! isset($entityPlan['source']['incremental_param'])) {
            throw new InvalidArgumentException('OpenAPI incremental extraction requires source.incremental_param.');
        }

        return $this->stream($entityPlan, $checkpoint);
    }

    /** @return array<string, mixed> */
    private function loadSpec(array $configuration): array
    {
        if (is_array($configuration['spec'] ?? null)) {
            return $configuration['spec'];
        }

        $contents = null;
        $formatHint = '';
        if (isset($configuration['spec_path'])) {
            $path = (string) $configuration['spec_path'];
            if (! is_file($path) || ! is_readable($path)) {
                throw new InvalidArgumentException('OpenAPI spec_path must be a readable file.');
            }
            $contents = file_get_contents($path);
            $formatHint = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        } elseif (isset($configuration['spec_url'])) {
            $url = trim((string) $configuration['spec_url']);
            $parts = parse_url($url);
            if (! is_array($parts) || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true) || empty($parts['host'])) {
                throw new InvalidArgumentException('OpenAPI spec_url must be absolute HTTP(S).');
            }
            if (! class_exists(Http::class)) {
                throw new RuntimeException('Laravel HTTP client is required for remote OpenAPI documents.');
            }
            $response = Http::acceptJson()->timeout(max(1, min(120, (int) ($configuration['timeout_seconds'] ?? 30))))->get($url)->throw();
            $contents = $response->body();
            $formatHint = strtolower(pathinfo((string) ($parts['path'] ?? ''), PATHINFO_EXTENSION));
        }

        if (! is_string($contents) || trim($contents) === '') {
            throw new InvalidArgumentException('OpenAPI connector requires spec, spec_path or spec_url.');
        }
        if (strlen($contents) > max(1024, (int) ($configuration['max_spec_bytes'] ?? 16777216))) {
            throw new RuntimeException('OpenAPI document exceeds the configured size bound.');
        }

        if ($formatHint === 'yaml' || $formatHint === 'yml' || ! str_starts_with(ltrim($contents), '{')) {
            if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
                $parsed = \Symfony\Component\Yaml\Yaml::parse($contents);
                if (is_array($parsed)) {
                    return $parsed;
                }
            }
            if (function_exists('yaml_parse')) {
                $parsed = yaml_parse($contents);
                if (is_array($parsed)) {
                    return $parsed;
                }
            }
            throw new RuntimeException('YAML OpenAPI parsing is unavailable; supply JSON or install a YAML parser.');
        }

        try {
            $parsed = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('OpenAPI JSON document is invalid.', 0, $exception);
        }
        if (! is_array($parsed)) {
            throw new RuntimeException('OpenAPI document must decode to an object.');
        }

        return $parsed;
    }

    /** @return array<string, mixed> */
    private function responseSchema(array $spec, array $operation): array
    {
        $responses = $operation['responses'] ?? [];
        if (! is_array($responses)) {
            return [];
        }
        $response = $responses['200'] ?? $responses['201'] ?? $responses['default'] ?? [];
        if (! is_array($response)) {
            return [];
        }
        $content = $response['content']['application/json']['schema'] ?? $response['schema'] ?? [];
        $schema = is_array($content) ? $this->resolveRef($spec, $content) : [];
        if (($schema['type'] ?? null) === 'array' && is_array($schema['items'] ?? null)) {
            $schema = $this->resolveRef($spec, $schema['items']);
        }

        return $schema;
    }

    /** @return array<int, array<string, mixed>> */
    private function fieldsFromSchema(array $spec, array $schema): array
    {
        $properties = $schema['properties'] ?? [];
        if (! is_array($properties)) {
            return [];
        }
        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];
        $fields = [];
        foreach ($properties as $name => $property) {
            if (! is_array($property)) {
                continue;
            }
            $property = $this->resolveRef($spec, $property);
            $fields[] = [
                'name' => (string) $name,
                'type' => $this->portableOpenApiType($property),
                'nullable' => ! in_array((string) $name, $required, true) || (bool) ($property['nullable'] ?? false),
                'format' => $property['format'] ?? null,
            ];
        }
        usort($fields, static fn (array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name']));

        return $fields;
    }

    /** @return array<string, mixed> */
    private function resolveRef(array $spec, array $schema): array
    {
        $ref = $schema['$ref'] ?? null;
        if (! is_string($ref) || ! str_starts_with($ref, '#/')) {
            return $schema;
        }
        $current = $spec;
        foreach (explode('/', substr($ref, 2)) as $segment) {
            $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return $schema;
            }
            $current = $current[$segment];
        }

        return is_array($current) ? $current : $schema;
    }

    private function portableOpenApiType(array $schema): string
    {
        $type = (string) ($schema['type'] ?? 'string');
        $format = (string) ($schema['format'] ?? '');

        return match ($type) {
            'integer' => 'integer',
            'number' => 'number',
            'boolean' => 'boolean',
            'object', 'array' => 'json',
            'string' => in_array($format, ['date', 'date-time'], true) ? 'datetime' : 'string',
            default => 'string',
        };
    }

    private function baseUrl(array $configuration, array $spec): string
    {
        $url = trim((string) ($configuration['base_url'] ?? $spec['servers'][0]['url'] ?? ''));
        $parts = parse_url($url);
        if ($url === '' || ! is_array($parts) || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true) || empty($parts['host'])) {
            throw new InvalidArgumentException('OpenAPI connector requires an absolute HTTP(S) base_url or servers[0].url.');
        }

        return rtrim($url, '/');
    }

    /** @param array<int, string> $fields
     *  @return array<int, array<string, string>>
     */
    private function keyHints(array $fields): array
    {
        foreach (['id', 'uuid'] as $field) {
            if (in_array($field, $fields, true)) {
                return [['field' => $field, 'kind' => 'candidate_primary']];
            }
        }

        return [];
    }

    /** @param array<int, string> $fields
     *  @return array<int, array<string, string>>
     */
    private function relationshipHints(array $fields): array
    {
        $relationships = [];
        foreach ($fields as $field) {
            if ($field !== 'id' && str_ends_with($field, '_id')) {
                $relationships[] = ['field' => $field, 'target_hint' => substr($field, 0, -3), 'kind' => 'inferred_foreign_key'];
            }
        }

        return $relationships;
    }
}
