<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\TemplateManagementContract;
use PDO;
use Foundation\Support\JsonHelper;

class TemplateManagement implements TemplateManagementContract
{
    private PDO $db;
    private string $tablePrefix = 'template_management_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createTemplate(
        string $tenantId,
        string $templateName,
        string $templateType,
        string $content,
        array $metadata = []
    ): string {
        $templateId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}templates (id, tenant_id, name, type, content, metadata, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $templateId,
            $tenantId,
            $templateName,
            $templateType,
            $content,
            json_encode($metadata),
            gmdate('c'),
        ]);

        return $templateId;
    }

    public function getTemplate(
        string $tenantId,
        string $templateId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}templates WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$templateId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['metadata'] = JsonHelper::decode($result['metadata']);
        }

        return $result ?: null;
    }

    public function updateTemplate(
        string $tenantId,
        string $templateId,
        string $content
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}templates SET content = ?, updated_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([$content, gmdate('c'), $templateId, $tenantId]);
    }

    public function renderTemplate(
        string $tenantId,
        string $templateId,
        array $variables
    ): string {
        $template = $this->getTemplate($tenantId, $templateId);

        if (!$template) {
            return '';
        }

        $rendered = $template['content'];

        foreach ($variables as $key => $value) {
            $rendered = str_replace("{{$key}}", (string)$value, $rendered);
        }

        return $rendered;
    }

    public function listTemplates(
        string $tenantId,
        string $templateType
    ): array {
        $stmt = $this->db->prepare(
            "SELECT id, name, type, created_at FROM {$this->tablePrefix}templates WHERE tenant_id = ? AND type = ? ORDER BY created_at DESC"
        );

        $stmt->execute([$tenantId, $templateType]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cloneTemplate(
        string $tenantId,
        string $sourceTemplateId,
        string $newTemplateName
    ): string {
        $source = $this->getTemplate($tenantId, $sourceTemplateId);

        if (!$source) {
            throw new \RuntimeException("Source template not found");
        }

        return $this->createTemplate(
            $tenantId,
            $newTemplateName,
            $source['type'],
            $source['content'],
            $source['metadata']
        );
    }

    public function previewTemplate(
        string $tenantId,
        string $templateId,
        array $sampleData
    ): string {
        return $this->renderTemplate($tenantId, $templateId, $sampleData);
    }

    public function deleteTemplate(
        string $tenantId,
        string $templateId
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->tablePrefix}templates WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([$templateId, $tenantId]);
    }
}
