<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Matching;

use InvalidArgumentException;

final readonly class DuplicateDecision
{
    public function __construct(
        public string $action,
        public bool $requiresReview,
        public string $reason,
        public string $matchType,
        public ?float $similarity = null,
        public ?float $risk = null,
    ) {
        if (! in_array($this->action, ['create', 'update', 'merge', 'skip', 'review'], true)) {
            throw new InvalidArgumentException('Unsupported duplicate decision action.');
        }
        if (! in_array($this->matchType, ['none', 'exact', 'fuzzy'], true)) {
            throw new InvalidArgumentException('Unsupported duplicate match type.');
        }
    }
}
