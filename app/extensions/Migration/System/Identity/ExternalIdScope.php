<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Identity;

use InvalidArgumentException;

final readonly class ExternalIdScope
{
    public function __construct(
        public int $companyId,
        public int $projectId,
        public int $connectionId,
        public string $sourceType,
        public string $sourceId,
    ) {
        foreach ([
            'company ID' => $this->companyId,
            'project ID' => $this->projectId,
            'connection ID' => $this->connectionId,
        ] as $label => $value) {
            if ($value <= 0) {
                throw new InvalidArgumentException("External ID {$label} must be positive.");
            }
        }

        if (trim($this->sourceType) === '' || trim($this->sourceId) === '') {
            throw new InvalidArgumentException('External ID source type and source ID cannot be empty.');
        }
    }

    public function key(): string
    {
        return hash('sha256', implode("\x1f", [
            (string) $this->companyId,
            (string) $this->projectId,
            (string) $this->connectionId,
            $this->sourceType,
            $this->sourceId,
        ]));
    }

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'project_id' => $this->projectId,
            'connection_id' => $this->connectionId,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
        ];
    }
}
