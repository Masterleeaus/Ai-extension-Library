<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingAutomation extends Model
{
    protected $table = 'ext_sm_pending_automations';

    protected $fillable = [
        'automation_id',
        'comment_data',
        'execute_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'comment_data' => 'array',
        'execute_at'   => 'datetime',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }
}
