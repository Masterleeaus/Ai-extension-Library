<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MigrationAuditEvent extends TenantMigrationModel
{
    protected $table = 'ext_migration_audit_events';

    protected $casts = [
        'company_id' => 'integer',
        'project_id' => 'integer',
        'run_id' => 'integer',
        'actor_id' => 'integer',
        'before_state' => 'array',
        'after_state' => 'array',
        'metadata' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(MigrationProject::class, 'project_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(MigrationRun::class, 'run_id');
    }
}
