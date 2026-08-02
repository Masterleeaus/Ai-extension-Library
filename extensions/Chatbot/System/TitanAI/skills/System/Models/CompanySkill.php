<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySkill extends Model
{
    protected $table = 'company_skills';

    protected $fillable = [
        'company_id',
        'skill_id',
        'skill_version_id',
        'update_policy',
        'installed_by',
        'is_enabled',
        'auto_use',
        'allowed_roles',
        'settings',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'auto_use' => 'boolean',
        'allowed_roles' => 'array',
        'settings' => 'array',
        'skill_version_id' => 'integer',
    ];

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(SkillVersion::class, 'skill_version_id');
    }

    public function installer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by');
    }
}
