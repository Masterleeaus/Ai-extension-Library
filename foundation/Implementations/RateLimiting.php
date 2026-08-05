<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\RateLimitingContract;
use PDO;
use Foundation\Support\JsonHelper;

class RateLimiting implements RateLimitingContract
{
    private PDO $db;
    private string $tablePrefix = 'rate_limiting_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function checkLimit(
        string $tenantId,
        string $identifier,
        string $bucket,
        int $limit,
        int $windowSeconds
    ): bool {
        $windowStart = date('c', time() - $windowSeconds);

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM {$this->tablePrefix}requests
             WHERE tenant_id = ? AND identifier = ? AND bucket = ? AND recorded_at >= ?"
        );

        $stmt->execute([$tenantId, $identifier, $bucket, $windowStart]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($result['count'] ?? 0) < $limit;
    }

    public function recordRequest(
        string $tenantId,
        string $identifier,
        string $bucket
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}requests (tenant_id, identifier, bucket, recorded_at)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([$tenantId, $identifier, $bucket, date('c')]);

        return $this->getCurrentRequestCount($tenantId, $identifier, $bucket);
    }

    public function getRemainingQuota(
        string $tenantId,
        string $identifier,
        string $bucket
    ): int {
        $policy = $this->getPolicy($tenantId, $bucket);

        if (!$policy) {
            return PHP_INT_MAX;
        }

        $limits = JsonHelper::decode($policy['limits']);
        $limit = $limits['limit'] ?? 100;
        $windowSeconds = $limits['window_seconds'] ?? 60;

        $currentCount = $this->getCurrentRequestCount($tenantId, $identifier, $bucket, $windowSeconds);

        return max(0, $limit - $currentCount);
    }

    public function resetQuota(
        string $tenantId,
        string $identifier,
        string $bucket
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->tablePrefix}requests
             WHERE tenant_id = ? AND identifier = ? AND bucket = ?"
        );

        return $stmt->execute([$tenantId, $identifier, $bucket]);
    }

    public function setPolicy(
        string $tenantId,
        string $policyName,
        array $limits
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}policies (tenant_id, name, limits, created_at)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE limits = ?, updated_at = ?"
        );

        $limitsJson = json_encode($limits);
        $now = date('c');

        return $stmt->execute([
            $tenantId,
            $policyName,
            $limitsJson,
            $now,
            $limitsJson,
            $now,
        ]);
    }

    public function getPolicy(
        string $tenantId,
        string $policyName
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}policies WHERE tenant_id = ? AND name = ?"
        );

        $stmt->execute([$tenantId, $policyName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['limits'] = JsonHelper::decode($result['limits']);
        }

        return $result ?: null;
    }

    public function getCurrentUsage(
        string $tenantId,
        string $identifier,
        string $bucket
    ): array {
        $policy = $this->getPolicy($tenantId, $bucket);

        if (!$policy) {
            return [
                'identifier' => $identifier,
                'bucket' => $bucket,
                'current_count' => 0,
                'limit' => PHP_INT_MAX,
                'remaining' => PHP_INT_MAX,
            ];
        }

        $limits = $policy['limits'];
        $limit = $limits['limit'] ?? 100;
        $windowSeconds = $limits['window_seconds'] ?? 60;

        $currentCount = $this->getCurrentRequestCount($tenantId, $identifier, $bucket, $windowSeconds);
        $remaining = max(0, $limit - $currentCount);

        return [
            'identifier' => $identifier,
            'bucket' => $bucket,
            'current_count' => $currentCount,
            'limit' => $limit,
            'remaining' => $remaining,
            'window_seconds' => $windowSeconds,
        ];
    }

    public function getMetrics(
        string $tenantId,
        ?string $bucket = null
    ): array {
        $query = "SELECT bucket, COUNT(*) as total_requests, MAX(recorded_at) as last_request
                  FROM {$this->tablePrefix}requests WHERE tenant_id = ?";
        $params = [$tenantId];

        if ($bucket) {
            $query .= " AND bucket = ?";
            $params[] = $bucket;
        }

        $query .= " GROUP BY bucket ORDER BY total_requests DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCurrentRequestCount(
        string $tenantId,
        string $identifier,
        string $bucket,
        int $windowSeconds = 60
    ): int {
        $windowStart = date('c', time() - $windowSeconds);

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM {$this->tablePrefix}requests
             WHERE tenant_id = ? AND identifier = ? AND bucket = ? AND recorded_at >= ?"
        );

        $stmt->execute([$tenantId, $identifier, $bucket, $windowStart]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'] ?? 0;
    }
}
