<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\CustomerIdentityContract;
use PDO;
use Foundation\Support\JsonHelper;

class CustomerIdentity implements CustomerIdentityContract
{
    private PDO $db;
    private string $tablePrefix = 'identity_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function resolve(
        string $tenantId,
        string $providerId,
        array $identifiers
    ): ?string {
        $stmt = $this->db->prepare(
            "SELECT customer_id FROM {$this->tablePrefix}mappings
             WHERE tenant_id = ? AND provider_id = ? AND identifiers = ?
             LIMIT 1"
        );

        $stmt->execute([
            $tenantId,
            $providerId,
            json_encode($identifiers),
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['customer_id'] : null;
    }

    public function link(
        string $tenantId,
        string $customerId,
        string $providerId,
        array $providerIdentity
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}mappings
             (tenant_id, customer_id, provider_id, identifiers, linked_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $tenantId,
            $customerId,
            $providerId,
            json_encode($providerIdentity),
            date('c'),
        ]);
    }

    public function getIdentities(
        string $tenantId,
        string $customerId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT provider_id, identifiers FROM {$this->tablePrefix}mappings
             WHERE tenant_id = ? AND customer_id = ?"
        );

        $stmt->execute([$tenantId, $customerId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $result['identifiers'] = JsonHelper::decode($result['identifiers']);
        }

        return $results;
    }

    public function unlink(
        string $tenantId,
        string $customerId,
        string $providerId
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->tablePrefix}mappings
             WHERE tenant_id = ? AND customer_id = ? AND provider_id = ?"
        );

        return $stmt->execute([$tenantId, $customerId, $providerId]);
    }

    public function getProfile(
        string $tenantId,
        string $customerId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}profiles
             WHERE tenant_id = ? AND customer_id = ?"
        );

        $stmt->execute([$tenantId, $customerId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['data'] = JsonHelper::decode($result['data']);
        }

        return $result ?: null;
    }

    public function updateProfile(
        string $tenantId,
        string $customerId,
        array $profile
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}profiles
             (tenant_id, customer_id, data, updated_at)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE data = ?, updated_at = ?"
        );

        $profileJson = json_encode($profile);
        return $stmt->execute([
            $tenantId,
            $customerId,
            $profileJson,
            date('c'),
            $profileJson,
            date('c'),
        ]);
    }
}
