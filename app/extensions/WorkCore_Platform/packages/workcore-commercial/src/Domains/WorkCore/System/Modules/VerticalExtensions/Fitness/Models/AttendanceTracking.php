<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Fitness\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceTracking extends Model
{
    protected $table = 'fitness_attendance_tracking';

    protected $fillable = [
        'company_id',
        'member_id',
        'class_schedule_id',
        'check_in_time',
        'check_out_time',
        'duration_minutes',
        'attendance_status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'duration_minutes' => 'integer',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo('App\Models\Customer', 'member_id');
    }

    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }
}
