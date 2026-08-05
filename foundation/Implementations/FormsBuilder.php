<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\FormsBuilderContract;
use PDO;
use Foundation\Support\JsonHelper;

class FormsBuilder implements FormsBuilderContract
{
    private PDO $db;
    private string $tablePrefix = 'forms_builder_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createForm(
        string $tenantId,
        string $formName,
        array $formSchema
    ): string {
        $formId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}forms (id, tenant_id, name, schema, created_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $formId,
            $tenantId,
            $formName,
            json_encode($formSchema),
            date('c'),
        ]);

        return $formId;
    }

    public function getForm(
        string $tenantId,
        string $formId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}forms WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$formId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['schema'] = JsonHelper::decode($result['schema']);
        }

        return $result ?: null;
    }

    public function updateForm(
        string $tenantId,
        string $formId,
        array $formSchema
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}forms SET schema = ?, updated_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([json_encode($formSchema), date('c'), $formId, $tenantId]);
    }

    public function addField(
        string $tenantId,
        string $formId,
        array $fieldDefinition
    ): bool {
        $form = $this->getForm($tenantId, $formId);

        if (!$form) {
            return false;
        }

        $schema = $form['schema'];
        $schema['fields'][] = $fieldDefinition;

        return $this->updateForm($tenantId, $formId, $schema);
    }

    public function removeField(
        string $tenantId,
        string $formId,
        string $fieldName
    ): bool {
        $form = $this->getForm($tenantId, $formId);

        if (!$form) {
            return false;
        }

        $schema = $form['schema'];
        $schema['fields'] = array_filter(
            $schema['fields'],
            fn($field) => ($field['name'] ?? null) !== $fieldName
        );

        return $this->updateForm($tenantId, $formId, $schema);
    }

    public function validateFormData(
        string $tenantId,
        string $formId,
        array $data
    ): array {
        $form = $this->getForm($tenantId, $formId);

        if (!$form) {
            return ['valid' => false, 'errors' => ['Form not found']];
        }

        $errors = [];
        $schema = $form['schema'];

        foreach ($schema['fields'] ?? [] as $field) {
            $fieldName = $field['name'];
            $value = $data[$fieldName] ?? null;

            if (($field['required'] ?? false) && empty($value)) {
                $errors[] = "Field '{$fieldName}' is required";
            }

            if (!empty($value) && isset($field['type'])) {
                if ($field['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Field '{$fieldName}' must be a valid email";
                }
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    public function listForms(
        string $tenantId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT id, name, created_at FROM {$this->tablePrefix}forms WHERE tenant_id = ? ORDER BY created_at DESC"
        );

        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteForm(
        string $tenantId,
        string $formId
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->tablePrefix}forms WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([$formId, $tenantId]);
    }
}
