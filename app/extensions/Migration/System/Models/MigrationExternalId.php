<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MigrationExternalId extends TenantMigrationModel
{
    protected $table = 'ext_migration_external_ids';

    protected $casts = [
        'company_id' => 'integer',
        'project_id' => 'integer',
        'entity_plan_id' => 'integer',
        'migrated_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(MigrationProject::class, 'project_id');
    }

    public function entityPlan(): BelongsTo
    {
        return $this->belongsTo(MigrationEntityPlan::class, 'entity_plan_id');
    }
}
