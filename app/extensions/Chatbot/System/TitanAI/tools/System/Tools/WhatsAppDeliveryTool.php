<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class WhatsAppDeliveryTool extends AbstractTool
{
    public static function id(): string { return 'whatsapp-delivery'; }
    public static function name(): string { return 'WhatsApp Delivery Tool'; }
    public static function operation(): string { return 'Deliver one WhatsApp message'; }
    public static function requiresApproval(): bool { return true; }
    public static function permissions(): array { return ['communications.whatsapp.send']; }
}
