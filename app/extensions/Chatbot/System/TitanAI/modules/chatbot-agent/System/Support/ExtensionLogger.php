<?php

declare(strict_types=1);

namespace App\Extensions\ChatbotAgent\System\Support;use Illuminate\Support\Facades\Log;
class ExtensionLogger{public static function info(string$message,array$context=[]):void{Log::info('[chatbot-agent] '.$message,$context);}public static function error(string$message,array$context=[]):void{Log::error('[chatbot-agent] '.$message,$context);}}
