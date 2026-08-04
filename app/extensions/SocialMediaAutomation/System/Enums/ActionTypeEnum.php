<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Enums;

enum ActionTypeEnum: string
{
    case Text = 'text';

    case Button = 'button';

    case Image = 'image';

    case QuickReplies = 'quick_replies';

    case Delay = 'delay';

    public function label(): string
    {
        return match ($this) {
            self::Text         => __('Text Message'),
            self::Button       => __('Button'),
            self::Image        => __('Image'),
            self::QuickReplies => __('Quick Replies'),
            self::Delay        => __('Delay'),
        };
    }
}
