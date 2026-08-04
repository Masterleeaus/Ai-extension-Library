<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class InternalChatDeliveryTool extends AbstractTool
{
    public static function id(): string { return 'internal-chat-delivery'; }
    public static function name(): string { return 'Internal Chat Delivery Tool'; }
    public static function operation(): string { return 'Deliver one internal chat message'; }
    public static function requiresApproval(): bool { return false; }
    public static function permissions(): array { return ['communications.internal.send']; }
}
