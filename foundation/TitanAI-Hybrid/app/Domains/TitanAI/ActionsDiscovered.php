<?php

declare(strict_types=1);

namespace App\Domains\TitanAI;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ActionsDiscovered
{
    use Dispatchable, SerializesModels;

    /** @param array<string, array<string, mixed>> $actions */
    public function __construct(
        public array $actions,
        public string $source = 'aiagent',
    ) {}
}
