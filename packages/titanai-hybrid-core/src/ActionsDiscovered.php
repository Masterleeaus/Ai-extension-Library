<?php

declare(strict_types=1);

namespace TitanAI\Hybrid;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ActionsDiscovered
{
    use Dispatchable, SerializesModels;

    public function __construct(public array $actions, public string $source = 'aiagent') {}
}
