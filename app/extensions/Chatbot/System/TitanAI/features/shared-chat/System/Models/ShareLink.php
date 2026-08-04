<?php

declare(strict_types=1);

namespace App\Extensions\SharedChat\System\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShareLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'url','category','chat','message','time','user_id','token_hash','expires_at','revoked_at','access_count','last_accessed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'access_count' => 'integer',
        'time' => 'integer',
    ];

    public function isAvailable(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
