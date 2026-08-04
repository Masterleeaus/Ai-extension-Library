<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class WebhookDeliveryTool extends AbstractTool
{
    public static function id(): string { return 'webhook-delivery'; }
    public static function name(): string { return 'Webhook Delivery Tool'; }
    public static function operation(): string { return 'Deliver one signed webhook event'; }
    public static function requiresApproval(): bool { return true; }
    public static function permissions(): array { return ['webhooks.send']; }
}
