<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\Api;

use App\Extensions\Migration\System\Connectors\AbstractSourceConnector;
use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use App\Extensions\Migration\System\Discovery\DiscoveryProfiler;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

final class RestConnector extends AbstractSourceConnector
{
    private ?DiscoveryProfiler $profiler = null;

    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'rest',
            name: 'REST API',
            connectorClass: self::class,
            category: 'api',
            authenticationTypes: ['bearer_token', 'api_key', 'headers'],
            capabilities: ['connection_test', 'resource_discovery', 'get_only', 'paged_stream', 'cursor_stream', 'incremental_parameter'],
            supportsDiscovery: true,
            supportsIncrementalSync: true,
            readOnly: true,
            streamingMode: 'paged',
        );
    }

    public function testConnection(array $configuration): array
    {
        try {
            $baseUrl = $this->baseUrl($configuration);
            $path = (string) ($configuration['health_path'] ?? '/');
            $response = $this->request($configuration)->get($this->url($baseUrl, $path), ['limit' => 1]);

            return [
                'successful' => $response->successful(),
                'connector' => 'rest',
                'status' => $response->status(),
                'configuration' => $this->redactConfiguration($configuration),
            ];
        } catch (\Throwable) {
            return [
                'successful' => false,
                'connector' => 'rest',
                'message' => 'REST source connection failed.',
                'configuration' => $this->redactConfiguration($configuration),
            ];
        }
    }

    public function discover(array $configuration): array
    {
        $baseUrl = $this->baseUrl($configuration);
        $resources = $configuration['resources'] ?? [];
        if (! is_array($resources)) {
            throw new InvalidArgumentException('REST resources configuration must be an array.');
        }

        $entities = [];
        foreach ($resources as $resource) {
            if (! is_array($resource)) {
                continue;
            }
            $name = trim((string) ($resource['name'] ?? ''));
            $path = trim((string) ($resource['path'] ?? ''));
            if ($name === '' || $path === '') {
                continue;
            }
            $sampleLimit = max(1, min(25, (int) ($resource['sample_limit'] ?? $configuration['sample_limit'] ?? 5)));
            $query = is_array($resource['query'] ?? null) ? $resource['query'] : [];
            $query[(string) ($resource['per_page_param'] ?? 'per_page')] = $sampleLimit;
            $response = $this->request($configuration)->get($this->url($baseUrl, $path), $query)->throw();
            $payload = $response->json();
            $rows = $this->rowsFromPayload($payload, (string) ($resource['data_path'] ?? 'data'));
            $profile = $this->profiler()->profile($rows, $name, $sampleLimit);
            $totalPath = (string) ($resource['total_path'] ?? 'meta.total');
            $total = $this->dataAt($payload, $totalPath);
            if (is_numeric($total)) {
                $profile['record_count'] = (int) $total;
            } else {
                $profile['record_count_known'] = false;
            }
            $profile['resource_path'] = $path;
            $entities[] = $profile;
        }

        return $this->discoveryResult($entities, [
            'base_url' => $this->redactUrl($baseUrl),
        ]);
    }

    public function stream(array $entityPlan, ?array $checkpoint = null): iterable
    {
        $configuration = $this->configurationFromPlan($entityPlan);
        $source = is_array($entityPlan['source'] ?? null) ? $entityPlan['source'] : [];
        $baseUrl = $this->baseUrl($configuration);
        $path = trim((string) ($source['path'] ?? $source['resource_path'] ?? $configuration['path'] ?? ''));
        if ($path === '') {
            throw new InvalidArgumentException('REST extraction requires a source path.');
        }

        $query = is_array($source['query'] ?? null) ? $source['query'] : [];
        if ($checkpoint !== null && isset($source['incremental_param'], $checkpoint['value'])) {
            $query[(string) $source['incremental_param']] = $checkpoint['value'];
        }

        $pageParam = (string) ($source['page_param'] ?? 'page');
        $perPageParam = (string) ($source['per_page_param'] ?? 'per_page');
        $dataPath = (string) ($source['data_path'] ?? 'data');
        $nextCursorPath = isset($source['next_cursor_path']) ? (string) $source['next_cursor_path'] : null;
        $cursorParam = (string) ($source['cursor_param'] ?? 'cursor');
        $pageSize = max(1, min(1000, (int) ($source['page_size'] ?? $configuration['page_size'] ?? 100)));
        $maxPages = max(1, min(100000, (int) ($configuration['max_pages'] ?? 1000)));
        $cursor = $checkpoint['cursor'] ?? null;

        for ($page = 1; $page <= $maxPages; $page++) {
            $pageQuery = $query;
            $pageQuery[$perPageParam] = $pageSize;
            if ($nextCursorPath !== null) {
                if ($cursor !== null && $cursor !== '') {
                    $pageQuery[$cursorParam] = $cursor;
                }
            } else {
                $pageQuery[$pageParam] = $page;
            }

            $response = $this->request($configuration)->get($this->url($baseUrl, $path), $pageQuery)->throw();
            $payload = $response->json();
            $rows = $this->rowsFromPayload($payload, $dataPath);
            $count = 0;
            foreach ($rows as $row) {
                $count++;
                yield $row;
            }

            if ($nextCursorPath !== null) {
                $next = $this->dataAt($payload, $nextCursorPath);
                if (! is_scalar($next) || (string) $next === '' || (string) $next === (string) $cursor) {
                    break;
                }
                $cursor = (string) $next;
                continue;
            }

            if ($count < $pageSize) {
                break;
            }
        }
    }

    public function readIncremental(array $entityPlan, array $checkpoint): iterable
    {
        if (! isset($entityPlan['source']['incremental_param'])) {
            throw new InvalidArgumentException('REST incremental extraction requires source.incremental_param.');
        }

        return $this->stream($entityPlan, $checkpoint);
    }

    private function request(array $configuration): PendingRequest
    {
        if (! class_exists(Http::class)) {
            throw new RuntimeException('Laravel HTTP client is required for REST migration sources.');
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

    private function baseUrl(array $configuration): string
    {
        $baseUrl = rtrim(trim((string) ($configuration['base_url'] ?? '')), '/');
        $parts = parse_url($baseUrl);
        if ($baseUrl === '' || ! is_array($parts) || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true) || empty($parts['host'])) {
            throw new InvalidArgumentException('REST connector requires an absolute HTTP(S) base_url.');
        }

        return $baseUrl;
    }

    private function url(string $baseUrl, string $path): string
    {
        if (preg_match('~^https?://~i', $path) === 1) {
            $parts = parse_url($path);
            $baseParts = parse_url($baseUrl);
            if (($parts['host'] ?? null) !== ($baseParts['host'] ?? null)) {
                throw new InvalidArgumentException('REST resource URLs must remain on the configured base host.');
            }
            return $path;
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }

    /** @return iterable<int, array<string, mixed>> */
    private function rowsFromPayload(mixed $payload, string $dataPath): iterable
    {
        $data = $dataPath === '' ? $payload : $this->dataAt($payload, $dataPath);
        if (! is_array($data)) {
            return;
        }
        if (! array_is_list($data)) {
            yield $data;
            return;
        }
        foreach ($data as $row) {
            if (is_array($row)) {
                yield array_is_list($row) ? ['value' => $row] : $row;
            } else {
                yield ['value' => $row];
            }
        }
    }

    private function dataAt(mixed $payload, string $path): mixed
    {
        if ($path === '') {
            return $payload;
        }
        $current = $payload;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    private function profiler(): DiscoveryProfiler
    {
        return $this->profiler ??= new DiscoveryProfiler();
    }

    private function redactUrl(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts)) {
            return '[INVALID URL]';
        }
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';

        return $scheme . '://' . $host . $port . $path;
    }
}
