<?php

declare(strict_types=1);
namespace App\Extensions\TitanAIGovernance\System\Models;
use Illuminate\Database\Eloquent\Model;
final class ToolPermission extends Model { protected $table='titan_ai_tool_permissions'; protected $guarded=[]; protected $casts=['allowed'=>'boolean','constraints'=>'array']; }
