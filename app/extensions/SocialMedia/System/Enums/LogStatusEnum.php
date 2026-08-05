<?php

declare(strict_types=1);


namespace App\Extensions\SocialMedia\System\Enums;

enum LogStatusEnum: string
{
    case success = 'success';

    case expired = 'expired';

    case failed = 'failed';
}
