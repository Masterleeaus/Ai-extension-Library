<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Memory;

final readonly class MemoryPolicy
{
    public function __construct(
        public MemoryScope $scope,
        public string $importance,
        public ?int $ttlSeconds,
        public bool $requiresApproval,
        public bool $containsPersonalData,
        public array $readRoles,
        public array $writeRoles,
    ) {}

    public function expires(): bool
    {
        return $this->ttlSeconds !== null;
    }
}
