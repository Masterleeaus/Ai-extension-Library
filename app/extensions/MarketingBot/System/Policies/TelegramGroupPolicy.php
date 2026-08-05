<?php

declare(strict_types=1);


namespace App\Extensions\MarketingBot\System\Policies;

use App\Extensions\MarketingBot\System\Models\Telegram\TelegramGroup;
use App\Models\User;

class TelegramGroupPolicy
{
    public function delete(User $user, TelegramGroup $item): bool
    {
        return $user->id === $item->user_id;
    }
}
