<?php

declare(strict_types=1);

namespace TitanAI\Hybrid;

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
