<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Forms;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class FormBuilder implements FormBuilderContract
{
    private string $id;
    private int $version;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        private int $tenantId,
        private string $name,
        private array $fields,
        private array $steps = [],
        private array $conditionalLogic = [],
        private array $validation = [],
        private ?string $description = null,
        private ?string $completionMessage = null,
        int $version = 1,
        bool $isActive = true,
        ?string $id = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id ?? Uuid::uuid4()->toString();
        $this->version = $version;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public function id(): string { return $this->id; }
    public function tenantId(): int { return $this->tenantId; }
    public function name(): string { return $this->name; }
    public function description(): ?string { return $this->description; }
    public function fields(): array { return $this->fields; }
    public function steps(): array { return $this->steps; }
    public function conditionalLogic(): array { return $this->conditionalLogic; }
    public function validation(): array { return $this->validation; }
    public function version(): int { return $this->version; }
    public function isActive(): bool { return $this->isActive; }
    public function completionMessage(): ?string { return $this->completionMessage; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function validate(array $data): array
    {
        $errors = [];

        foreach ($this->fields as $field) {
            $fieldName = $field['name'] ?? '';
            $isRequired = $field['required'] ?? false;
            $type = $field['type'] ?? 'text';

            if ($isRequired && empty($data[$fieldName])) {
                $errors[$fieldName][] = "{$fieldName} is required";
            }

            if (isset($data[$fieldName])) {
                $errors = array_merge($errors, $this->validateField($fieldName, $data[$fieldName], $field));
            }
        }

        return $errors;
    }

    private function validateField(string $name, mixed $value, array $field): array
    {
        $errors = [];
        $type = $field['type'] ?? 'text';

        return match ($type) {
            'email' => $this->validateEmail($name, $value, $errors),
            'phone' => $this->validatePhone($name, $value, $errors),
            'date' => $this->validateDate($name, $value, $errors),
            'number' => $this->validateNumber($name, $value, $errors),
            default => $errors,
        };
    }

    private function validateEmail(string $name, mixed $value, array $errors): array
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$name][] = "Invalid email address";
        }
        return $errors;
    }

    private function validatePhone(string $name, mixed $value, array $errors): array
    {
        if (!preg_match('/^\+?[1-9]\d{1,14}$/', (string) $value)) {
            $errors[$name][] = "Invalid phone number";
        }
        return $errors;
    }

    private function validateDate(string $name, mixed $value, array $errors): array
    {
        if (!strtotime((string) $value)) {
            $errors[$name][] = "Invalid date format";
        }
        return $errors;
    }

    private function validateNumber(string $name, mixed $value, array $errors): array
    {
        if (!is_numeric($value)) {
            $errors[$name][] = "Must be a number";
        }
        return $errors;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'name' => $this->name,
            'description' => $this->description,
            'fields' => $this->fields,
            'steps' => $this->steps,
            'conditionalLogic' => $this->conditionalLogic,
            'validation' => $this->validation,
            'completionMessage' => $this->completionMessage,
            'version' => $this->version,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c'),
        ];
    }

    public static function from(array $data): self
    {
        return new self(
            tenantId: $data['tenantId'] ?? $data['tenant_id'] ?? throw new \InvalidArgumentException('tenantId required'),
            name: $data['name'] ?? throw new \InvalidArgumentException('name required'),
            fields: $data['fields'] ?? [],
            steps: $data['steps'] ?? [],
            conditionalLogic: $data['conditionalLogic'] ?? $data['conditional_logic'] ?? [],
            validation: $data['validation'] ?? [],
            description: $data['description'],
            completionMessage: $data['completionMessage'] ?? $data['completion_message'],
            version: $data['version'] ?? 1,
            isActive: $data['isActive'] ?? $data['is_active'] ?? true,
            id: $data['id'],
            createdAt: isset($data['createdAt']) ? new DateTimeImmutable($data['createdAt']) : null,
            updatedAt: isset($data['updatedAt']) ? new DateTimeImmutable($data['updatedAt']) : null,
        );
    }
}
