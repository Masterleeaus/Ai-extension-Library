<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Customization\Templates;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class Template implements TemplateContract
{
    private string $id;
    private int $version;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        private int $tenantId,
        private string $name,
        private string $type,
        private string $content,
        private array $variables = [],
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
    public function type(): string { return $this->type; }
    public function content(): string { return $this->content; }
    public function variables(): array { return $this->variables; }
    public function version(): int { return $this->version; }
    public function isActive(): bool { return $this->isActive; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function render(array $variables = []): string
    {
        $content = $this->content;
        $allVars = array_merge($this->variables, $variables);

        foreach ($allVars as $name => $value) {
            $content = str_replace('{{' . $name . '}}', (string) $value, $content);
        }

        return $content;
    }

    public static function from(array $data): self
    {
        return new self(
            tenantId: $data['tenantId'] ?? $data['tenant_id'] ?? throw new \InvalidArgumentException('tenantId required'),
            name: $data['name'] ?? throw new \InvalidArgumentException('name required'),
            type: $data['type'] ?? throw new \InvalidArgumentException('type required'),
            content: $data['content'] ?? throw new \InvalidArgumentException('content required'),
            variables: $data['variables'] ?? [],
            version: $data['version'] ?? 1,
            isActive: $data['isActive'] ?? $data['is_active'] ?? true,
            id: $data['id'],
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
            'type' => $this->type,
            'content' => $this->content,
            'variables' => $this->variables,
            'version' => $this->version,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c'),
        ];
    }
}
