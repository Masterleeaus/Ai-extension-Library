<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Destination;

use InvalidArgumentException;

final readonly class DestinationWriteContext
{
    public function __construct(
        public int $companyId,
        public int $projectId,
        public int $connectionId,
        public ?int $runId = null,
        public ?int $actorId = null,
        public bool $dryRun = false,
    ) {
        foreach (['companyId' => $this->companyId, 'projectId' => $this->projectId, 'connectionId' => $this->connectionId] as $name => $value) {
            if ($value <= 0) {
                throw new InvalidArgumentException("Destination write {$name} must be positive.");
            }
        }
    }
}
