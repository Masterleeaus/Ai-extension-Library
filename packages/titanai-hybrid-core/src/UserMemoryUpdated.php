<?php

declare(strict_types=1);

namespace TitanAI\Hybrid;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserMemoryUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $entityType,
        public mixed $entityId,
        public string $key,
        public mixed $value,
        public string $source,
    ) {}
}
