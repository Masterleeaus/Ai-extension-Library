<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationLog extends Model
{
    protected $table = 'ext_sm_automation_logs';

    protected $fillable = [
        'automation_id',
        'platform_comment_id',
        'commenter_id',
        'commenter_username',
        'comment_text',
        'actions_executed',
        'status',
        'error_message',
    ];

    protected $casts = [
        'actions_executed' => 'array',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class, 'automation_id');
    }
}
