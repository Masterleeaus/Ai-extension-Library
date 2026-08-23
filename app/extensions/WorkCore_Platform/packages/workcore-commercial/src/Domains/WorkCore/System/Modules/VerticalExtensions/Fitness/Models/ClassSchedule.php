<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Fitness\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSchedule extends Model
{
    protected $table = 'fitness_class_schedules';

    protected $fillable = [
        'company_id',
        'class_name',
        'class_type',
        'trainer_id',
        'scheduled_date',
        'start_time',
        'end_time',
        'capacity',
        'current_enrollment',
        'waitlist_count',
        'difficulty_level',
        'status',
        'metadata',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'capacity' => 'integer',
        'current_enrollment' => 'integer',
        'waitlist_count' => 'integer',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Worker', 'trainer_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(AttendanceTracking::class);
    }
}
