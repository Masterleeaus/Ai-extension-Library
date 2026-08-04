<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\ResearchEngineContract;
use PDO;

class ResearchEngine implements ResearchEngineContract
{
    private PDO $db;
    private string $tablePrefix = 'research_';

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
            "INSERT INTO {$this->tablePrefix}sessions (id, tenant_id, topic, parameters, status, started_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $researchId,
            $tenantId,
            $topic,
            json_encode($parameters),
            'active',
            date('c'),
        ]);

        return $researchId;
    }

    public function getResearchStatus(
        string $tenantId,
        string $researchId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}sessions WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$researchId, $tenantId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($session) {
            $session['parameters'] = json_decode($session['parameters'], true);
        }

        return $session ?: null;
    }

    public function getFindings(
        string $tenantId,
        string $researchId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}findings WHERE tenant_id = ? AND research_id = ? ORDER BY created_at DESC"
        );

        $stmt->execute([$tenantId, $researchId]);
        $findings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($findings as &$finding) {
            $finding['data'] = json_decode($finding['data'], true);
        }

        return $findings;
    }

    public function getCitations(
        string $tenantId,
        string $researchId,
        ?string $findingId = null
    ): array {
        $query = "SELECT * FROM {$this->tablePrefix}citations WHERE tenant_id = ? AND research_id = ?";
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
            "UPDATE {$this->tablePrefix}sessions SET status = ?, paused_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['paused', date('c'), $researchId, $tenantId]);
    }

    public function resumeResearch(
        string $tenantId,
        string $researchId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}sessions SET status = ?, resumed_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['active', date('c'), $researchId, $tenantId]);
    }

    public function cancelResearch(
        string $tenantId,
        string $researchId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}sessions SET status = ?, cancelled_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['cancelled', date('c'), $researchId, $tenantId]);
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
            "UPDATE {$this->tablePrefix}findings SET verified = 1, verification_evidence = ?, verified_at = ? WHERE id = ? AND tenant_id = ? AND research_id = ?"
        );

        return $stmt->execute([
            json_encode($evidence),
            date('c'),
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
