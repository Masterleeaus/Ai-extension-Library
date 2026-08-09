<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MigrationConnection extends TenantMigrationModel
{
    protected $table = 'ext_migration_connections';

    protected $hidden = [
        'credential_reference',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'project_id' => 'integer',
        'user_id' => 'integer',
        'team_id' => 'integer',
        'credential_reference' => 'encrypted',
        'settings' => 'array',
        'last_tested_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(MigrationProject::class, 'project_id');
    }
}
