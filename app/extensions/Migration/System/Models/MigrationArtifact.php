<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MigrationArtifact extends TenantMigrationModel
{
    protected $table = 'ext_migration_artifacts';

    protected $casts = [
        'company_id' => 'integer',
        'project_id' => 'integer',
        'run_id' => 'integer',
        'size_bytes' => 'integer',
        'metadata' => 'array',
        'expires_at' => 'datetime',
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
