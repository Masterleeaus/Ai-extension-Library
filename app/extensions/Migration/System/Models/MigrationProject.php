<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

final class MigrationProject extends TenantMigrationModel
{
    protected $table = 'ext_migration_projects';

    protected $casts = [
        'company_id' => 'integer',
        'user_id' => 'integer',
        'team_id' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'settings' => 'array',
        'metadata' => 'array',
    ];

    public function connections(): HasMany
    {
        return $this->hasMany(MigrationConnection::class, 'project_id');
    }

    public function entityPlans(): HasMany
    {
        return $this->hasMany(MigrationEntityPlan::class, 'project_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(MigrationRun::class, 'project_id');
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(MigrationArtifact::class, 'project_id');
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(MigrationAuditEvent::class, 'project_id');
    }
}
