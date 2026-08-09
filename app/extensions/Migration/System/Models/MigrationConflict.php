<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MigrationConflict extends TenantMigrationModel
{
    protected $table = 'ext_migration_conflicts';

    protected $casts = [
        'company_id' => 'integer',
        'run_id' => 'integer',
        'entity_plan_id' => 'integer',
        'source_snapshot' => 'array',
        'target_snapshot' => 'array',
        'resolution' => 'array',
        'resolved_by' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(MigrationRun::class, 'run_id');
    }

    public function entityPlan(): BelongsTo
    {
        return $this->belongsTo(MigrationEntityPlan::class, 'entity_plan_id');
    }
}
