<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationReply extends Model
{
    protected $table = 'ext_sm_automation_replies';

    protected $fillable = [
        'automation_id',
        'content',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class, 'automation_id');
    }
}
