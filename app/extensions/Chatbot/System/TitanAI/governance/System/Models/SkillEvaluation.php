<?php

declare(strict_types=1);
namespace App\Extensions\TitanAIGovernance\System\Models;
use Illuminate\Database\Eloquent\Model;
final class SkillEvaluation extends Model { protected $table='titan_ai_skill_evaluations'; protected $guarded=[]; protected $casts=['metrics'=>'array','passed'=>'boolean','evaluated_at'=>'datetime']; }
