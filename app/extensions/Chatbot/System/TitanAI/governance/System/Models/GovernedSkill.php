<?php

declare(strict_types=1);
namespace App\Extensions\TitanAIGovernance\System\Models;
use Illuminate\Database\Eloquent\Model;
final class GovernedSkill extends Model { protected $table='titan_ai_governed_skills'; protected $guarded=[]; protected $casts=['definition'=>'array','active'=>'boolean']; }
