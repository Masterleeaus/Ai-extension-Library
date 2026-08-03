<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIPhotoshootProduct extends Model
{
    use HasFactory;

    protected $table = 'ai_photo_studio_products';

    protected $fillable = [
        'user_id',
        'name',
        'image_url',
        'exist_type',
        'status',
        'generation_uuid',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
