<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\WebhookSecurityContract;
use PDO;
use Foundation\Support\JsonHelper;

class WebhookSecurity implements WebhookSecurityContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'webhook_security_';
    private const TABLE_DELIVERIES = self::TABLE_PREFIX . 'deliveries';
    private const TABLE_ENDPOINTS = self::TABLE_PREFIX . 'endpoints';
    private string $tablePrefix = self::TABLE_PREFIX;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function registerEndpoint(
        string $tenantId,
        string $endpointUrl,
        array $events,
        string $secret
    ): string {
        $endpointId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_ENDPOINTS . " (id, tenant_id, url, events, secret, active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $endpointId,
            $tenantId,
            $endpointUrl,
            json_encode($events),
            hash('sha256', $secret),
            1,
            DateTimeHelper::now(),
        ]);

        return $endpointId;
    }

    public function unregisterEndpoint(
        string $tenantId,
        string $endpointId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_ENDPOINTS . " SET active = 0, deactivated_at = ?
             WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([DateTimeHelper::now(), $endpointId, $tenantId]);
    }

    public function validateSignature(
        string $payload,
        string $signature,
        string $secret
    ): bool {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    public function verifyTenant(
        string $endpointId,
        string $tenantId
    ): bool {
        $stmt = $this->db->prepare(
            "SELECT id FROM " . self::TABLE_ENDPOINTS . " WHERE id = ? AND tenant_id = ? AND active = 1"
        );

        $stmt->execute([$endpointId, $tenantId]);
        return $stmt->rowCount() > 0;
    }

    public function dispatchEvent(
        string $tenantId,
        string $eventType,
        array $payload
    ): array {
        $webhookId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_ENDPOINTS . " WHERE tenant_id = ? AND active = 1"
        );

        $stmt->execute([$tenantId]);
        $endpoints = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dispatchResults = [];

        foreach ($endpoints as $endpoint) {
            $events = JsonHelper::decode($endpoint['events']);

            if (in_array($eventType, $events)) {
                $signature = hash_hmac('sha256', json_encode($payload), $endpoint['secret']);

                $deliveryId = bin2hex(random_bytes(16));
                $deliveryStmt = $this->db->prepare(
                    "INSERT INTO " . self::TABLE_DELIVERIES . " (id, webhook_id, endpoint_id, event_type, payload, signature, status, attempts, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $deliveryStmt->execute([
                    $deliveryId,
                    $webhookId,
                    $endpoint['id'],
                    $eventType,
                    json_encode($payload),
                    $signature,
                    'pending',
                    0,
                    DateTimeHelper::now(),
                ]);

                $dispatchResults[] = [
                    'delivery_id' => $deliveryId,
                    'endpoint_id' => $endpoint['id'],
                    'status' => 'pending',
                ];
            }
        }

        return [
            'webhook_id' => $webhookId,
            'event_type' => $eventType,
            'dispatched_count' => count($dispatchResults),
            'deliveries' => $dispatchResults,
        ];
    }

    public function retryFailed(
        string $tenantId,
        string $webhookId,
        int $maxAttempts = 3
    ): bool {
        $stmt = $this->db->prepare(
            "SELECT d.* FROM " . self::TABLE_DELIVERIES . " d
             JOIN " . self::TABLE_ENDPOINTS . " e ON d.endpoint_id = e.id
             WHERE e.tenant_id = ? AND d.webhook_id = ? AND d.status = 'failed' AND d.attempts < ?"
        );

        $stmt->execute([$tenantId, $webhookId, $maxAttempts]);
        $failures = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($failures as $delivery) {
            $updateStmt = $this->db->prepare(
                "UPDATE " . self::TABLE_DELIVERIES . " SET status = 'pending', attempts = attempts + 1
                 WHERE id = ?"
            );

            $updateStmt->execute([$delivery['id']]);
        }

        return true;
    }

    public function getDeliveryStatus(
        string $tenantId,
        string $webhookId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT d.* FROM " . self::TABLE_DELIVERIES . " d
             JOIN " . self::TABLE_ENDPOINTS . " e ON d.endpoint_id = e.id
             WHERE e.tenant_id = ? AND d.webhook_id = ?
             ORDER BY d.created_at DESC
             LIMIT 1"
        );

        $stmt->execute([$tenantId, $webhookId]);
        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($delivery) {
            $delivery['payload'] = JsonHelper::decode($delivery['payload']);
        }

        return $delivery ?: null;
    }

    public function listEndpoints(
        string $tenantId,
        ?string $event = null
    ): array {
        $query = "SELECT * FROM " . self::TABLE_ENDPOINTS . " WHERE tenant_id = ? AND active = 1";
        $params = [$tenantId];

        if ($event) {
            $query .= " AND JSON_CONTAINS(events, ?)";
            $params[] = json_encode($event);
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $result['events'] = JsonHelper::decode($result['events']);
            unset($result['secret']);
        }

        return $results;
    }
}
