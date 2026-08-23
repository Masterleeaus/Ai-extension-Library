<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Destination;

use InvalidArgumentException;

final readonly class DestinationWriteResult
{
    /** @param array<string, mixed> $payload
     *  @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $action,
        public string $targetId,
        public array $payload = [],
        public array $metadata = [],
    ) {
        if (! in_array($this->action, ['created', 'updated', 'merged', 'skipped', 'review'], true)) {
            throw new InvalidArgumentException('Unsupported destination write result action.');
        }
        if ($this->action !== 'skipped' && $this->action !== 'review' && trim($this->targetId) === '') {
            throw new InvalidArgumentException('Destination target ID is required for committed writes.');
        }
    }
}
