<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MigrationStep extends TenantMigrationModel
{
    protected $table = 'ext_migration_steps';

    protected $casts = [
        'company_id' => 'integer',
        'run_id' => 'integer',
        'entity_plan_id' => 'integer',
        'sequence' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'counters' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(MigrationRun::class, 'run_id');
    }

    public function entityPlan(): BelongsTo
    {
        return $this->belongsTo(MigrationEntityPlan::class, 'entity_plan_id');
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(MigrationCheckpoint::class, 'step_id');
    }

    public function recordResults(): HasMany
    {
        return $this->hasMany(MigrationRecordResult::class, 'step_id');
    }

    public function failures(): HasMany
    {
        return $this->hasMany(MigrationFailure::class, 'step_id');
    }
}
