<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Fitness\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutProgram extends Model
{
    protected $table = 'fitness_workout_programs';

    protected $fillable = [
        'company_id',
        'member_id',
        'trainer_id',
        'program_name',
        'program_type',
        'start_date',
        'end_date',
        'duration_weeks',
        'frequency',
        'goal_description',
        'current_progress',
        'exercises',
        'status',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_weeks' => 'integer',
        'exercises' => 'json',
        'current_progress' => 'json',
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

    public function trainer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Worker', 'trainer_id');
    }
}
