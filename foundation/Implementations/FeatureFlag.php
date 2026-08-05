<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\FeatureFlagContract;
use PDO;
use Foundation\Support\JsonHelper;

class FeatureFlag implements FeatureFlagContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'feature_flags_';
    private const TABLE_FLAGS = self::TABLE_PREFIX . 'flags';
    private const TABLE_USER_VARIANTS = self::TABLE_PREFIX . 'user_variants';
    private string $tablePrefix = self::TABLE_PREFIX;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createFlag(
        string $tenantId,
        string $flagName,
        bool $enabled,
        array $metadata = []
    ): string {
        $flagId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_FLAGS . " (id, tenant_id, name, enabled, metadata, rollout_percentage, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $flagId,
            $tenantId,
            $flagName,
            $enabled ? 1 : 0,
            json_encode($metadata),
            100,
            date('c'),
        ]);

        return $flagId;
    }

    public function getFlag(
        string $tenantId,
        string $flagName
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_FLAGS . " WHERE tenant_id = ? AND name = ?"
        );

        $stmt->execute([$tenantId, $flagName]);
        $flag = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($flag) {
            $flag['metadata'] = JsonHelper::decode($flag['metadata']);
        }

        return $flag ?: null;
    }

    public function isEnabled(
        string $tenantId,
        string $flagName,
        ?string $userId = null
    ): bool {
        $flag = $this->getFlag($tenantId, $flagName);

        if (!$flag || !$flag['enabled']) {
            return false;
        }

        if ($userId) {
            $userVariant = $this->getUserVariant($tenantId, $flagName, $userId);

            if ($userVariant !== null) {
                return $userVariant;
            }
        }

        $rolloutPercentage = $flag['rollout_percentage'] ?? 100;

        if ($rolloutPercentage < 100) {
            $hash = crc32($userId ?? $tenantId . $flagName);
            $percentage = ($hash % 100) + 1;

            return $percentage <= $rolloutPercentage;
        }

        return true;
    }

    public function enableFlag(
        string $tenantId,
        string $flagName,
        ?int $rolloutPercentage = null
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_FLAGS . " SET enabled = 1, rollout_percentage = ?, updated_at = ? WHERE tenant_id = ? AND name = ?"
        );

        return $stmt->execute([
            $rolloutPercentage ?? 100,
            date('c'),
            $tenantId,
            $flagName,
        ]);
    }

    public function disableFlag(
        string $tenantId,
        string $flagName
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_FLAGS . " SET enabled = 0, updated_at = ? WHERE tenant_id = ? AND name = ?"
        );

        return $stmt->execute([date('c'), $tenantId, $flagName]);
    }

    public function setRollout(
        string $tenantId,
        string $flagName,
        int $percentage
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_FLAGS . " SET rollout_percentage = ?, updated_at = ? WHERE tenant_id = ? AND name = ?"
        );

        return $stmt->execute([$percentage, date('c'), $tenantId, $flagName]);
    }

    public function addUserVariant(
        string $tenantId,
        string $flagName,
        string $userId,
        bool $enabled
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_USER_VARIANTS . " (tenant_id, flag_name, user_id, enabled, created_at)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE enabled = ?, updated_at = ?"
        );

        $now = date('c');

        return $stmt->execute([
            $tenantId,
            $flagName,
            $userId,
            $enabled ? 1 : 0,
            $now,
            $enabled ? 1 : 0,
            $now,
        ]);
    }

    public function listFlags(string $tenantId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_FLAGS . " WHERE tenant_id = ? ORDER BY created_at DESC"
        );

        $stmt->execute([$tenantId]);
        $flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($flags as &$flag) {
            $flag['metadata'] = JsonHelper::decode($flag['metadata']);
        }

        return $flags;
    }

    private function getUserVariant(string $tenantId, string $flagName, string $userId): ?bool {
        $stmt = $this->db->prepare(
            "SELECT enabled FROM " . self::TABLE_USER_VARIANTS . " WHERE tenant_id = ? AND flag_name = ? AND user_id = ?"
        );

        $stmt->execute([$tenantId, $flagName, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (bool)$result['enabled'] : null;
    }
}
