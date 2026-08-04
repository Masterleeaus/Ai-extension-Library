<?php

declare(strict_types=1);

namespace App\Domains\TitanAI;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WorkflowMemoryUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $entityType,
        public string $workflowId,
        public string $key,
        public mixed $value,
        public string $source,
    ) {}
}
