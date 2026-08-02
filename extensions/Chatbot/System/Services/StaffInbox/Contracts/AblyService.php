<?php

namespace App\Extensions\Chatbot\System\Services\StaffInbox\Contracts;

use Ably\AblyRest;

class AblyService
{
    public static function ablyRest(): AblyRest
    {
        return new AblyRest(self::apiKey());
    }

    public static function apiKey(): ?string
    {
        return setting('ably_private_key', '');
    }
}
