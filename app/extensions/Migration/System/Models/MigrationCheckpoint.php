<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MigrationCheckpoint extends TenantMigrationModel
{
    protected $table = 'ext_migration_checkpoints';

    protected $casts = [
        'company_id' => 'integer',
        'run_id' => 'integer',
        'step_id' => 'integer',
        'cursor' => 'array',
        'metadata' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(MigrationRun::class, 'run_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(MigrationStep::class, 'step_id');
    }
}
