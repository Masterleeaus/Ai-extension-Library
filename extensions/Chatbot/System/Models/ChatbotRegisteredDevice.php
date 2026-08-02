<?php
namespace App\Extensions\Chatbot\System\Models;
use Illuminate\Database\Eloquent\Model;
class ChatbotRegisteredDevice extends Model { protected $table='ext_chatbot_registered_devices'; protected $guarded=[]; protected $casts=['capabilities'=>'array','last_seen_at'=>'datetime','revoked_at'=>'datetime']; }
