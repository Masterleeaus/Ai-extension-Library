<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Enums;

enum MigrationPermission: string
{
    case VIEW = 'migration.view';
    case CONFIGURE = 'migration.configure';
    case APPROVE = 'migration.approve';
    case EXECUTE = 'migration.execute';
    case RESOLVE = 'migration.resolve';
    case ROLLBACK = 'migration.rollback';
    case PURGE = 'migration.purge';
}
