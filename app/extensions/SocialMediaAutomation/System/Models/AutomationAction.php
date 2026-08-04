<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationAction extends Model
{
    protected $table = 'ext_sm_automation_actions';

    protected $fillable = [
        'automation_id',
        'type',
        'content',
        'order',
    ];

    protected $casts = [
        'content' => 'array',
        'order'   => 'integer',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class, 'automation_id');
    }
}
