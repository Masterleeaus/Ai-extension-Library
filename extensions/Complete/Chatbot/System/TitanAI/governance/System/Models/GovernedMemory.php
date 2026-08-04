<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Models;

use Illuminate\Database\Eloquent\Model;

final class GovernedMemory extends Model
{
    protected $table = 'titan_ai_governed_memories';
    protected $guarded = [];
    protected $casts = ['payload'=>'array','metadata'=>'array','approved_at'=>'datetime','expires_at'=>'datetime','last_reinforced_at'=>'datetime'];
}
