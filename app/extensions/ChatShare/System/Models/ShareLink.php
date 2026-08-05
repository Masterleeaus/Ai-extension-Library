<?php

declare(strict_types=1);


namespace App\Extensions\ChatShare\System\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShareLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'category',
        'chat',
        'message',
        'time',
    ];
}
