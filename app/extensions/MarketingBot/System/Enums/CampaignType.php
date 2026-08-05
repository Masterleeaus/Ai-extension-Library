<?php

declare(strict_types=1);


namespace App\Extensions\MarketingBot\System\Enums;

enum CampaignType: string
{
    case telegram = 'telegram';
    case whatsapp = 'whatsapp';
}
