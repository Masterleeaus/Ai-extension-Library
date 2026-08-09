<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Models;

use App\Extensions\Migration\System\Enums\MigrationRunState;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MigrationRun extends TenantMigrationModel
{
    protected $table = 'ext_migration_runs';

    protected $casts = [
        'company_id' => 'integer',
        'project_id' => 'integer',
        'requested_by' => 'integer',
        'approved_by' => 'integer',
        'state' => MigrationRunState::class,
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rolled_back_at' => 'datetime',
        'counters' => 'array',
        'options' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(MigrationProject::class, 'project_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(MigrationStep::class, 'run_id');
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(MigrationCheckpoint::class, 'run_id');
    }

    public function recordResults(): HasMany
    {
        return $this->hasMany(MigrationRecordResult::class, 'run_id');
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(MigrationConflict::class, 'run_id');
    }

    public function failures(): HasMany
    {
        return $this->hasMany(MigrationFailure::class, 'run_id');
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(MigrationArtifact::class, 'run_id');
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(MigrationAuditEvent::class, 'run_id');
    }
}
