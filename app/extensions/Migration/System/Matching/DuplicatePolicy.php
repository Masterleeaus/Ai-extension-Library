<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Matching;

enum DuplicatePolicy: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case MERGE = 'merge';
    case SKIP = 'skip';
    case REVIEW = 'review';
}
