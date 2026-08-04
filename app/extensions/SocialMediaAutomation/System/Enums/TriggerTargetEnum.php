<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Enums;

enum TriggerTargetEnum: string
{
    case SpecificPost = 'specific_post';

    case AllPosts = 'all_posts';

    case NextPost = 'next_post';

    public function label(): string
    {
        return match ($this) {
            self::SpecificPost => __('Specific Post'),
            self::AllPosts     => __('All Posts'),
            self::NextPost     => __('Next Post'),
        };
    }
}
