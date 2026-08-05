<?php

declare(strict_types=1);


namespace App\Extensions\SocialMedia\System\Enums;

enum StatusEnum: string
{
    case pending = 'pending';

    case draft = 'draft';

    case published = 'published';

    case scheduled = 'scheduled';

    case failed = 'failed';
}
