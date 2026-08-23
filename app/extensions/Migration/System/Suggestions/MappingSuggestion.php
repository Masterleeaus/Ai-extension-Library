<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Suggestions;

use InvalidArgumentException;

final readonly class MappingSuggestion
{
    /** @param array<string, mixed> $evidence */
    public function __construct(
        public string $sourceField,
        public string $targetField,
        public float $confidence,
        public array $evidence,
        public string $status = 'advisory',
        public bool $requiresApproval = true,
        public ?int $approvedBy = null,
        public ?string $approvedAt = null,
    ) {
        if (trim($this->sourceField) === '' || trim($this->targetField) === '') {
            throw new InvalidArgumentException('Mapping suggestion source and target fields are required.');
        }
        if ($this->confidence < 0.0 || $this->confidence > 1.0) {
            throw new InvalidArgumentException('Mapping suggestion confidence must be between 0 and 1.');
        }
        if (! in_array($this->status, ['advisory', 'approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Unsupported mapping suggestion status.');
        }
        if ($this->status === 'approved' && ($this->approvedBy === null || $this->approvedBy <= 0)) {
            throw new InvalidArgumentException('Approved mapping suggestion requires an approval actor.');
        }
    }

    public function approve(int $actorId, string $approvedAt): self
    {
        if ($actorId <= 0) {
            throw new InvalidArgumentException('Mapping suggestion approval actor must be positive.');
        }

        return new self(
            sourceField: $this->sourceField,
            targetField: $this->targetField,
            confidence: $this->confidence,
            evidence: $this->evidence,
            status: 'approved',
            requiresApproval: false,
            approvedBy: $actorId,
            approvedAt: $approvedAt,
        );
    }
}
