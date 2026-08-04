<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Enums;

enum ImageStatusEnum: string
{
    case pending = 'PENDING';
    case processing = 'PROCESSING';
    case completed = 'COMPLETED';
    case failed = 'FAILED';
}
