<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Channels\Push;

use App\Domains\TitanAI\Channels\Contracts\AbstractChannel;

final class PushChannel extends AbstractChannel
{
    public function id(): string { return 'push'; }
    public function capabilities(): array { return ['notifications', 'deep_links', 'delivery_receipts']; }
}
