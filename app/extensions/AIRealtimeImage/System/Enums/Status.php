<?php

declare(strict_types=1);


namespace App\Extensions\AIRealtimeImage\System\Enums;

enum Status: string
{
    case pending = 'pending';

    case failed = 'failed';

    case success = 'success';
}
