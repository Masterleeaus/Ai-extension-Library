<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\Api;

use App\Extensions\Migration\System\Connectors\AbstractSourceConnector;
use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use App\Extensions\Migration\System\Discovery\DiscoveryProfiler;
use App\Extensions\Migration\System\Security\GraphQlReadOnlyGuard;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

final class GraphqlConnector extends AbstractSourceConnector
{
    private const INTROSPECTION_QUERY = <<<'GRAPHQL'
query TitanMigrationSchema {
  __schema {
    queryType {
      fields {
        name
        description
        type { kind name ofType { kind name ofType { kind name } } }
        args { name type { kind name ofType { kind name } } }
      }
    }
  }
}
GRAPHQL;

    private ?GraphQlReadOnlyGuard $guard = null;
    private ?DiscoveryProfiler $profiler = null;

    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'graphql',
            name: 'GraphQL API',
            connectorClass: self::class,
            category: 'api',
            authenticationTypes: ['bearer_token', 'api_key', 'headers'],
            capabilities: ['connection_test', 'introspection_discovery', 'query_only', 'paged_stream', 'cursor_stream', 'incremental_variable'],
            supportsDiscovery: true,
            supportsIncrementalSync: true,
            readOnly: true,
            streamingMode: 'paged',
        );
    }

    public function testConnection(array $configuration): array
    {
        try {
            $payload = $this->execute($configuration, self::INTROSPECTION_QUERY, []);

            return [
                'successful' => isset($payload['data']['__schema']),
                'connector' => 'graphql',
                'configuration' => $this->redactConfiguration($configuration),
            ];
        } catch (\Throwable) {
            return [
                'successful' => false,
                'connector' => 'graphql',
                'message' => 'GraphQL source connection or introspection failed.',
                'configuration' => $this->redactConfiguration($configuration),
            ];
        }
    }

    public function discover(array $configuration): array
    {
        $payload = $this->execute($configuration, self::INTROSPECTION_QUERY, []);
        $fields = $payload['data']['__schema']['queryType']['fields'] ?? [];
        $entities = [];

        if (is_array($fields)) {
            foreach ($fields as $field) {
                if (! is_array($field) || ! isset($field['name'])) {
                    continue;
                }
                $entities[] = [
                    'name' => (string) $field['name'],
                    'record_count' => null,
                    'record_count_known' => false,
                    'fields' => [],
                    'keys' => [],
                    'relationships' => [],
                    'incremental_fields' => [],
                    'attachment_fields' => [],
                    'samples' => [],
                    'graphql_type' => $this->typeName($field['type'] ?? null),
                    'arguments' => $field['args'] ?? [],
                ];
            }
        }

        $resources = $configuration['resources'] ?? [];
        if (is_array($resources)) {
            foreach ($resources as $resource) {
                if (! is_array($resource) || ! isset($resource['name'], $resource['query'])) {
                    continue;
                }
                $query = $this->guard()->assertReadOnly((string) $resource['query']);
                $variables = is_array($resource['variables'] ?? null) ? $resource['variables'] : [];
                $samplePayload = $this->execute($configuration, $query, $variables);
                $rows = $this->rowsAt($samplePayload, (string) ($resource['data_path'] ?? 'data'));
                $profile = $this->profiler()->profile($rows, (string) $resource['name'], max(1, min(25, (int) ($resource['sample_limit'] ?? 5))));
                $profile['record_count_known'] = false;
                $this->replaceEntity($entities, $profile);
            }
        }

        return $this->discoveryResult($entities, ['endpoint' => $this->redactedEndpoint($this->endpoint($configuration))]);
    }

    public function stream(array $entityPlan, ?array $checkpoint = null): iterable
    {
        $configuration = $this->configurationFromPlan($entityPlan);
        $source = is_array($entityPlan['source'] ?? null) ? $entityPlan['source'] : [];
        $query = $source['query'] ?? null;
        if (! is_string($query) || trim($query) === '') {
            throw new InvalidArgumentException('GraphQL extraction requires source.query.');
        }
        $query = $this->guard()->assertReadOnly($query);
        $variables = is_array($source['variables'] ?? null) ? $source['variables'] : [];
        if ($checkpoint !== null && isset($source['incremental_variable'], $checkpoint['value'])) {
            $variables[(string) $source['incremental_variable']] = $checkpoint['value'];
        }

        $dataPath = (string) ($source['data_path'] ?? 'data');
        $nextCursorPath = isset($source['next_cursor_path']) ? (string) $source['next_cursor_path'] : null;
        $cursorVariable = (string) ($source['cursor_variable'] ?? 'after');
        $pageSizeVariable = isset($source['page_size_variable']) ? (string) $source['page_size_variable'] : null;
        $pageSize = max(1, min(1000, (int) ($source['page_size'] ?? 100)));
        $maxPages = max(1, min(100000, (int) ($configuration['max_pages'] ?? 1000)));
        $cursor = $checkpoint['cursor'] ?? null;

        for ($page = 1; $page <= $maxPages; $page++) {
            $pageVariables = $variables;
            if ($pageSizeVariable !== null && $pageSizeVariable !== '') {
                $pageVariables[$pageSizeVariable] = $pageSize;
            }
            if ($nextCursorPath !== null && $cursor !== null && $cursor !== '') {
                $pageVariables[$cursorVariable] = $cursor;
            }

            $payload = $this->execute($configuration, $query, $pageVariables);
            $count = 0;
            foreach ($this->rowsAt($payload, $dataPath) as $row) {
                $count++;
                yield $row;
            }

            if ($nextCursorPath === null) {
                break;
            }
            $next = $this->dataAt($payload, $nextCursorPath);
            if (! is_scalar($next) || (string) $next === '' || (string) $next === (string) $cursor) {
                break;
            }
            $cursor = (string) $next;
            if ($count === 0) {
                break;
            }
        }
    }

    public function readIncremental(array $entityPlan, array $checkpoint): iterable
    {
        if (! isset($entityPlan['source']['incremental_variable'])) {
            throw new InvalidArgumentException('GraphQL incremental extraction requires source.incremental_variable.');
        }

        return $this->stream($entityPlan, $checkpoint);
    }

    /** @return array<string, mixed> */
    private function execute(array $configuration, string $query, array $variables): array
    {
        $query = $this->guard()->assertReadOnly($query);
        $response = $this->request($configuration)
            ->post($this->endpoint($configuration), ['query' => $query, 'variables' => $variables])
            ->throw();
        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('GraphQL response must be a JSON object.');
        }
        if (! empty($payload['errors'])) {
            throw new RuntimeException('GraphQL source returned query errors.');
        }

        return $payload;
    }

    private function request(array $configuration): PendingRequest
    {
        if (! class_exists(Http::class)) {
            throw new RuntimeException('Laravel HTTP client is required for GraphQL migration sources.');
        }
        $request = Http::acceptJson()->timeout(max(1, min(120, (int) ($configuration['timeout_seconds'] ?? 30))));
        $headers = is_array($configuration['headers'] ?? null) ? $configuration['headers'] : [];
        if ($headers !== []) {
            $request = $request->withHeaders(array_map('strval', $headers));
        }
        $token = $configuration['bearer_token'] ?? null;
        if (is_string($token) && $token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }

    private function endpoint(array $configuration): string
    {
        $endpoint = trim((string) ($configuration['endpoint'] ?? ''));
        $parts = parse_url($endpoint);
        if ($endpoint === '' || ! is_array($parts) || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true) || empty($parts['host'])) {
            throw new InvalidArgumentException('GraphQL connector requires an absolute HTTP(S) endpoint.');
        }

        return $endpoint;
    }

    /** @return iterable<int, array<string, mixed>> */
    private function rowsAt(array $payload, string $path): iterable
    {
        $data = $this->dataAt($payload, $path);
        if (! is_array($data)) {
            return;
        }
        if (! array_is_list($data)) {
            yield $data;
            return;
        }
        foreach ($data as $row) {
            yield is_array($row) && ! array_is_list($row) ? $row : ['value' => $row];
        }
    }

    private function dataAt(mixed $payload, string $path): mixed
    {
        $current = $payload;
        foreach (array_filter(explode('.', $path), static fn (string $part): bool => $part !== '') as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    private function typeName(mixed $type): ?string
    {
        if (! is_array($type)) {
            return null;
        }
        if (! empty($type['name'])) {
            return (string) $type['name'];
        }

        return $this->typeName($type['ofType'] ?? null);
    }

    private function replaceEntity(array &$entities, array $profile): void
    {
        foreach ($entities as $index => $entity) {
            if (($entity['name'] ?? null) === ($profile['name'] ?? null)) {
                $entities[$index] = array_merge($entity, $profile);
                return;
            }
        }
        $entities[] = $profile;
    }

    private function guard(): GraphQlReadOnlyGuard
    {
        return $this->guard ??= new GraphQlReadOnlyGuard();
    }

    private function profiler(): DiscoveryProfiler
    {
        return $this->profiler ??= new DiscoveryProfiler();
    }

    private function redactedEndpoint(string $endpoint): string
    {
        $parts = parse_url($endpoint);
        if (! is_array($parts)) {
            return '[INVALID URL]';
        }

        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '') . ($parts['path'] ?? '');
    }
}
