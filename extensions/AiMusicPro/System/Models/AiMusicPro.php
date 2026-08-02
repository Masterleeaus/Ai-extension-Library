<?php

namespace App\Extensions\AiMusicPro\System\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiMusicPro extends Model
{
    use HasFactory;

    protected $table = 'ai_music_pro';

    protected $fillable = [
        'user_id',
        'file_path',
        'workbook_title',
        'ai_music_prompt',
        'duration',
        'music_style',
        'provider',
        'lyrics',
    ];
}
