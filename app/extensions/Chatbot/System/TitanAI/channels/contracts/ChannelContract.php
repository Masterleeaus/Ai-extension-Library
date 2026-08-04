<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Channels\Contracts;

interface ChannelContract
{
    public function id(): string;
    public function capabilities(): array;
    public function supportsInbound(): bool;
    public function supportsOutbound(): bool;
}
