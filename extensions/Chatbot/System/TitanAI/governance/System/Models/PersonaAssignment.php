<?php

declare(strict_types=1);
namespace App\Extensions\TitanAIGovernance\System\Models;
use Illuminate\Database\Eloquent\Model;
final class PersonaAssignment extends Model { protected $table='titan_ai_persona_assignments'; protected $guarded=[]; protected $casts=['definition'=>'array','active'=>'boolean']; }
