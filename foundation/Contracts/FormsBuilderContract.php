<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface FormsBuilderContract
{
    public function createForm(
        string $tenantId,
        string $formName,
        array $formSchema
    ): string;

    public function getForm(
        string $tenantId,
        string $formId
    ): ?array;

    public function updateForm(
        string $tenantId,
        string $formId,
        array $formSchema
    ): bool;

    public function addField(
        string $tenantId,
        string $formId,
        array $fieldDefinition
    ): bool;

    public function removeField(
        string $tenantId,
        string $formId,
        string $fieldName
    ): bool;

    public function validateFormData(
        string $tenantId,
        string $formId,
        array $data
    ): array;

    public function listForms(
        string $tenantId
    ): array;

    public function deleteForm(
        string $tenantId,
        string $formId
    ): bool;
}
