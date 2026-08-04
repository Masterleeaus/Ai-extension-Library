<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Enums;

enum AutomationStatusEnum: string
{
    case Draft = 'draft';

    case Live = 'live';

    case Paused = 'paused';

    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft   => __('Draft'),
            self::Live    => __('Live'),
            self::Paused  => __('Paused'),
            self::Expired => __('Expired'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft   => 'bg-foreground/5 text-foreground',
            self::Live    => 'bg-green-500/10 text-green-600',
            self::Paused  => 'bg-yellow-500/10 text-yellow-600',
            self::Expired => 'bg-red-500/10 text-red-600',
        };
    }
}
