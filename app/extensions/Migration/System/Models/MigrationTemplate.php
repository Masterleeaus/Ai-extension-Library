<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Models;

final class MigrationTemplate extends TenantMigrationModel
{
    protected $table = 'ext_migration_templates';

    protected $casts = [
        'company_id' => 'integer',
        'user_id' => 'integer',
        'team_id' => 'integer',
        'mapping_schema' => 'array',
        'settings' => 'array',
    ];
}
