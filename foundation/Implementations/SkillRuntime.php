<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\SkillRuntimeContract;
use PDO;

class SkillRuntime implements SkillRuntimeContract
{
    private PDO $db;
    private string $tablePrefix = 'skills_';
    private array $registry = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function register(
        string $skillId,
        string $className,
        array $metadata = []
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}registry (skill_id, class_name, metadata, registered_at)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE metadata = ?"
        );

        $metadataJson = json_encode($metadata);
        return $stmt->execute([
            $skillId,
            $className,
            $metadataJson,
            date('c'),
            $metadataJson,
        ]);
    }

    public function execute(
        string $tenantId,
        string $skillId,
        array $input,
        array $context = []
    ): array {
        $executionId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}executions (id, tenant_id, skill_id, input, context, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $executionId,
            $tenantId,
            $skillId,
            json_encode($input),
            json_encode($context),
            'running',
            date('c'),
        ]);

        return [
            'execution_id' => $executionId,
            'status' => 'running',
        ];
    }

    public function getSkill(
        string $skillId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}registry WHERE skill_id = ?"
        );
        $stmt->execute([$skillId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['metadata'] = json_decode($result['metadata'], true);
        }

        return $result ?: null;
    }

    public function listSkills(
        string $tenantId,
        ?string $category = null
    ): array {
        $query = "SELECT * FROM {$this->tablePrefix}registry";
        $params = [];

        if ($category) {
            $query .= " WHERE JSON_EXTRACT(metadata, '$.category') = ?";
            $params[] = $category;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function validateInput(
        string $skillId,
        array $input
    ): bool {
        $skill = $this->getSkill($skillId);
        if (!$skill) {
            return false;
        }

        $requiredFields = $skill['metadata']['required_fields'] ?? [];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                return false;
            }
        }

        return true;
    }

    public function publishVersion(
        string $skillId,
        string $version,
        array $metadata = []
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}versions (skill_id, version, metadata, published_at)
             VALUES (?, ?, ?, ?)"
        );

        return $stmt->execute([
            $skillId,
            $version,
            json_encode($metadata),
            date('c'),
        ]);
    }
}
