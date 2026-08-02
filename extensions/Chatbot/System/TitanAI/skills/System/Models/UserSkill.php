<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserSkill extends Pivot
{
    protected $table = 'user_skills';

    protected $fillable = [
        'user_id',
        'skill_id',
        'skill_version_id',
        'update_policy',
        'auto_use',
    ];

    protected $casts = [
        'auto_use' => 'boolean',
        'skill_version_id' => 'integer',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(SkillVersion::class, 'skill_version_id');
    }
}
