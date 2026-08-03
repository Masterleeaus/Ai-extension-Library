<?php

declare(strict_types=1);

namespace App\Domains\TitanAI;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ActionFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $source,
        public string $actionKey,
        public ?string $userId = null,
        public ?\Throwable $exception = null,
        public array $payload = [],
        public array $metadata = [],
    ) {}
}
