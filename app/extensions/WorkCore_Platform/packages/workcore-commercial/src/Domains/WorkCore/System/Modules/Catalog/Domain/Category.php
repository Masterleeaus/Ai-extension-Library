<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Domain;

use InvalidArgumentException;

final class Category
{
    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $children
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $parentId = null,
        public readonly ?string $description = null,
        public readonly int $sortOrder = 0,
        public readonly bool $isActive = true,
        public readonly array $metadata = [],
        public readonly array $children = [],
    ) {
        if (trim($id) === '' || trim($companyId) === '' || trim($name) === '' || trim($slug) === '') {
            throw new InvalidArgumentException('Category id, company, name and slug are required.');
        }
    }

    public function getHierarchy(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parentId,
            'children' => $this->children,
        ];
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    public function getChildCount(): int
    {
        return count($this->children);
    }
}
