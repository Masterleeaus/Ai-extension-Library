<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class PaymentLinkTool extends AbstractTool
{
    public static function id(): string { return 'payment-link'; }
    public static function name(): string { return 'Payment Link Tool'; }
    public static function operation(): string { return 'Generate a payment link for an invoice'; }
    public static function requiresApproval(): bool { return true; }
    public static function permissions(): array { return ['payments.links.create']; }
}
