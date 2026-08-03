<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Channels\InternalChat;

use App\Domains\TitanAI\Channels\Contracts\AbstractChannel;

final class InternalChatChannel extends AbstractChannel
{
    public function id(): string { return 'internal-chat'; }
    public function capabilities(): array { return ['messages', 'threads', 'mentions', 'attachments', 'presence']; }
}
