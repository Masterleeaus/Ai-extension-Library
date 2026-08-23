<?php

namespace App\Models;

use App\Domains\MultiTenant\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use BelongsToCompany;

    protected $table = 'channels';
    protected $guarded = [];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    protected $fillable = [
        'company_id',
        'user_id',
        'team_id',
    ];
}