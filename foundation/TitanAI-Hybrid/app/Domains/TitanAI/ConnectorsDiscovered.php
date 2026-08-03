<?php

declare(strict_types=1);

namespace App\Domains\TitanAI;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ConnectorsDiscovered
{
    use Dispatchable, SerializesModels;

    /** @param array<string, array<string, mixed>> $connectors */
    public function __construct(
        public array $connectors,
        public string $source = 'aichatpro',
    ) {}
}
