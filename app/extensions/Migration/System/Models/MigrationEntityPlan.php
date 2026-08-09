<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MigrationEntityPlan extends TenantMigrationModel
{
    protected $table = 'ext_migration_entity_plans';

    protected $casts = [
        'company_id' => 'integer',
        'project_id' => 'integer',
        'sequence' => 'integer',
        'enabled' => 'boolean',
        'dependency_keys' => 'array',
        'settings' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(MigrationProject::class, 'project_id');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(MigrationMapping::class, 'entity_plan_id');
    }

    public function externalIds(): HasMany
    {
        return $this->hasMany(MigrationExternalId::class, 'entity_plan_id');
    }
}
