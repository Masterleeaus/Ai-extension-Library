<?php

declare(strict_types=1);

namespace App\Domains\TitanAI;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ConnectorStateChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $connectorKey,
        public string $state,
        public string $reason = '',
        public string $source = '',
    ) {}
}
