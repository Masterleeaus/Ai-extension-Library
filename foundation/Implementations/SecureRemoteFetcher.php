<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\SecureRemoteFetcherContract;
use PDO;
use Foundation\Support\DateTimeHelper;

class SecureRemoteFetcher implements SecureRemoteFetcherContract
{
    private PDO $db;
    private string $tablePrefix = 'remote_fetch_';
    private int $maxRedirects = 5;
    private int $timeout = 30;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function fetch(
        string $tenantId,
        string $url,
        array $options = []
    ): array {
        if (!$this->validateUrl($url)) {
            return ['error' => 'Invalid URL', 'status' => 'rejected'];
        }

        $domain = parse_url($url, PHP_URL_HOST);
        if (!$this->isAllowedDomain($domain)) {
            return ['error' => 'Domain not allowed', 'status' => 'blocked'];
        }

        $startTime = microtime(true);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => $this->maxRedirects,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $content = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $bytesTransferred = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        curl_close($ch);

        $duration = microtime(true) - $startTime;

        $this->recordFetch($tenantId, $url, $statusCode, (int)$bytesTransferred, $duration);

        if ($statusCode !== 200) {
            return [
                'error' => "HTTP {$statusCode}",
                'status' => 'failed',
                'status_code' => $statusCode,
            ];
        }

        return [
            'content' => $content,
            'status' => 'success',
            'status_code' => $statusCode,
            'bytes' => (int)$bytesTransferred,
            'duration' => $duration,
        ];
    }

    public function validateUrl(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0);
    }

    public function isAllowedDomain(string $domain): bool {
        $blocked = $this->getBlockedDomains();
        return !in_array($domain, $blocked);
    }

    public function setRateLimit(
        string $tenantId,
        int $requestsPerMinute
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}limits (tenant_id, requests_per_minute, set_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE requests_per_minute = ?"
        );

        return $stmt->execute([$tenantId, $requestsPerMinute, DateTimeHelper::now(), $requestsPerMinute]);
    }

    public function getRateLimit(string $tenantId): ?int {
        $stmt = $this->db->prepare(
            "SELECT requests_per_minute FROM {$this->tablePrefix}limits WHERE tenant_id = ?"
        );

        $stmt->execute([$tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['requests_per_minute'] : null;
    }

    public function recordFetch(
        string $tenantId,
        string $url,
        int $statusCode,
        int $bytesTransferred,
        float $duration
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}history (tenant_id, url, status_code, bytes_transferred, duration, recorded_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $tenantId,
            $url,
            $statusCode,
            $bytesTransferred,
            $duration,
            DateTimeHelper::now(),
        ]);
    }

    public function getBlockedDomains(): array {
        $stmt = $this->db->prepare(
            "SELECT domain FROM {$this->tablePrefix}blocked"
        );

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_column($results, 'domain');
    }

    public function addBlockedDomain(string $domain): bool {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO {$this->tablePrefix}blocked (domain, added_at) VALUES (?, ?)"
        );

        return $stmt->execute([$domain, DateTimeHelper::now()]);
    }

    public function removeBlockedDomain(string $domain): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->tablePrefix}blocked WHERE domain = ?"
        );

        return $stmt->execute([$domain]);
    }
}
