<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Models;

use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Automation extends Model
{
    protected $table = 'ext_sm_automations';

    protected $fillable = [
        'user_id',
        'social_media_platform_id',
        'name',
        'status',
        'trigger_target',
        'trigger_post_id',
        'trigger_post_data',
        'keyword_mode',
        'include_keywords',
        'exclude_keywords',
        'enable_public_replies',
        'delay_seconds',
        'workflow_graph',
    ];

    protected $casts = [
        'trigger_post_data'     => 'array',
        'include_keywords'      => 'array',
        'exclude_keywords'      => 'array',
        'enable_public_replies' => 'boolean',
        'delay_seconds'         => 'integer',
        'workflow_graph'        => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(SocialMediaPlatform::class, 'social_media_platform_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AutomationAction::class, 'automation_id')->orderBy('order');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(AutomationReply::class, 'automation_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class, 'automation_id');
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
