<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Channels\Contracts;

abstract class AbstractChannel implements ChannelContract
{
    public function supportsInbound(): bool { return true; }
    public function supportsOutbound(): bool { return true; }
}
