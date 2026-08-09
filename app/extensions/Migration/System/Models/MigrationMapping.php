<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MigrationMapping extends TenantMigrationModel
{
    protected $table = 'ext_migration_mappings';

    protected $casts = [
        'company_id' => 'integer',
        'entity_plan_id' => 'integer',
        'transform_config' => 'array',
        'required' => 'boolean',
    ];

    public function entityPlan(): BelongsTo
    {
        return $this->belongsTo(MigrationEntityPlan::class, 'entity_plan_id');
    }
}
