<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Channels\WebChat;

use App\Domains\TitanAI\Channels\Contracts\AbstractChannel;

final class WebChatChannel extends AbstractChannel
{
    public function id(): string { return 'web-chat'; }
    public function capabilities(): array { return ['visitor_chat', 'lead_capture', 'handoff', 'attachments', 'typing']; }
}
