<?php

declare(strict_types=1);


namespace App\Extensions\Chatbot\System\Enums;

use App\Enums\Traits\EnumTo;

enum PositionEnum: string
{
    use EnumTo;

    case left = 'left';
    case right = 'right';
}
