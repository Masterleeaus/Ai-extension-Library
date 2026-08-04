<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Prompts;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class PromptTemplate implements PromptTemplateContract
{
    private string $id;
    private int $version;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?int $actorId;
    private ?string $causationId;

    public function __construct(
        private int $tenantId,
        private string $name,
        private string $template,
        private string $category = 'general',
        private ?string $description = null,
        private array $variables = [],
        private array $metadata = [],
        ?string $id = null,
        int $version = 1,
        bool $isActive = true,
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
    public function category(): string { return $this->category; }
    public function template(): string { return $this->template; }
    public function variables(): array { return $this->variables; }
    public function version(): int { return $this->version; }
    public function isActive(): bool { return $this->isActive; }
    public function metadata(): array { return $this->metadata; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function render(array $variables = []): string
    {
        $template = $this->template;
        $allVars = array_merge($this->variables, $variables);

        foreach ($allVars as $name => $value) {
            $template = str_replace('{{' . $name . '}}', (string) $value, $template);
            $template = str_replace('${' . $name . '}', (string) $value, $template);
        }

        return $template;
    }

    public function createNewVersion(string $newTemplate, ?string $description = null): self
    {
        return new self(
            tenantId: $this->tenantId,
            name: $this->name,
            template: $newTemplate,
            category: $this->category,
            description: $description ?? $this->description,
            variables: $this->variables,
            metadata: $this->metadata,
            id: $this->id,
            version: $this->version + 1,
            isActive: true,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            tenantId: $this->tenantId,
            name: $this->name,
            template: $this->template,
            category: $this->category,
            description: $this->description,
            variables: $this->variables,
            metadata: $this->metadata,
            id: $this->id,
            version: $this->version,
            isActive: false,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }

    public static function from(array $data): self
    {
        return new self(
            tenantId: $data['tenantId'] ?? $data['tenant_id'] ?? throw new \InvalidArgumentException('tenantId required'),
            name: $data['name'] ?? throw new \InvalidArgumentException('name required'),
            template: $data['template'] ?? throw new \InvalidArgumentException('template required'),
            category: $data['category'] ?? 'general',
            description: $data['description'],
            variables: $data['variables'] ?? [],
            metadata: $data['metadata'] ?? [],
            id: $data['id'],
            version: $data['version'] ?? 1,
            isActive: $data['isActive'] ?? $data['is_active'] ?? true,
            createdAt: isset($data['createdAt']) ? new DateTimeImmutable($data['createdAt']) : null,
            updatedAt: isset($data['updatedAt']) ? new DateTimeImmutable($data['updatedAt']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'template' => $this->template,
            'variables' => $this->variables,
            'version' => $this->version,
            'isActive' => $this->isActive,
            'metadata' => $this->metadata,
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c'),
        ];
    }
}
