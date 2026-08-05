<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\ResearchEngineContract;
use PDO;
use Foundation\Support\JsonHelper;

class ResearchEngine implements ResearchEngineContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'research_';
    private const TABLE_CITATIONS = self::TABLE_PREFIX . 'citations';
    private const TABLE_FINDINGS = self::TABLE_PREFIX . 'findings';
    private const TABLE_SESSIONS = self::TABLE_PREFIX . 'sessions';
    private string $tablePrefix = self::TABLE_PREFIX;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function startResearch(
        string $tenantId,
        string $topic,
        array $parameters = []
    ): string {
        $researchId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_SESSIONS . " (id, tenant_id, topic, parameters, status, started_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $researchId,
            $tenantId,
            $topic,
            json_encode($parameters),
            'active',
            DateTimeHelper::now(),
        ]);

        return $researchId;
    }

    public function getResearchStatus(
        string $tenantId,
        string $researchId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_SESSIONS . " WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$researchId, $tenantId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($session) {
            $session['parameters'] = JsonHelper::decode($session['parameters']);
        }

        return $session ?: null;
    }

    public function getFindings(
        string $tenantId,
        string $researchId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_FINDINGS . " WHERE tenant_id = ? AND research_id = ? ORDER BY created_at DESC"
        );

        $stmt->execute([$tenantId, $researchId]);
        $findings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($findings as &$finding) {
            $finding['data'] = JsonHelper::decode($finding['data']);
        }

        return $findings;
    }

    public function getCitations(
        string $tenantId,
        string $researchId,
        ?string $findingId = null
    ): array {
        $query = "SELECT * FROM " . self::TABLE_CITATIONS . " WHERE tenant_id = ? AND research_id = ?";
        $params = [$tenantId, $researchId];

        if ($findingId) {
            $query .= " AND finding_id = ?";
            $params[] = $findingId;
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function pauseResearch(
        string $tenantId,
        string $researchId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_SESSIONS . " SET status = ?, paused_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['paused', DateTimeHelper::now(), $researchId, $tenantId]);
    }

    public function resumeResearch(
        string $tenantId,
        string $researchId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_SESSIONS . " SET status = ?, resumed_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['active', DateTimeHelper::now(), $researchId, $tenantId]);
    }

    public function cancelResearch(
        string $tenantId,
        string $researchId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_SESSIONS . " SET status = ?, cancelled_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['cancelled', DateTimeHelper::now(), $researchId, $tenantId]);
    }

    public function exportFindings(
        string $tenantId,
        string $researchId,
        string $format
    ): string {
        $findings = $this->getFindings($tenantId, $researchId);

        if ($format === 'json') {
            return json_encode($findings, JSON_PRETTY_PRINT);
        } elseif ($format === 'csv') {
            return $this->exportAsCsv($findings);
        }

        return '';
    }

    public function verifyFinding(
        string $tenantId,
        string $researchId,
        string $findingId,
        array $evidence
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_FINDINGS . " SET verified = 1, verification_evidence = ?, verified_at = ? WHERE id = ? AND tenant_id = ? AND research_id = ?"
        );

        return $stmt->execute([
            json_encode($evidence),
            DateTimeHelper::now(),
            $findingId,
            $tenantId,
            $researchId,
        ]);
    }

    private function exportAsCsv(array $findings): string {
        $csv = "ID,Topic,Status,Created At\n";

        foreach ($findings as $finding) {
            $csv .= "{$finding['id']},{$finding['research_id']},{$finding['status']},{$finding['created_at']}\n";
        }

        return $csv;
    }
}
