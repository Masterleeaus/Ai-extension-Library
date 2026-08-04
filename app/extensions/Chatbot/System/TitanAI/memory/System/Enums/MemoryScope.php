<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatMemory\System\Enums;

enum MemoryScope: string
{
    case User = 'user';
    case Company = 'company';
    case Team = 'team';
    case Customer = 'customer';
    case Property = 'property';
    case Job = 'job';
    case Conversation = 'conversation';
    case Worker = 'worker';
}
