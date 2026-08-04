<?php

declare(strict_types=1);

namespace TitanAI\Hybrid;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ActionCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $source,
        public string $actionKey,
        public ?string $userId = null,
        public mixed $result = null,
        public array $metadata = [],
    ) {}
}
