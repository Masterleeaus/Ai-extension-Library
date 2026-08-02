<?php
namespace App\Extensions\Reviews\System\Support;use Illuminate\Support\Facades\Log;
class ExtensionLogger{public static function info(string$message,array$context=[]):void{Log::info('[reviews] '.$message,$context);}public static function error(string$message,array$context=[]):void{Log::error('[reviews] '.$message,$context);}}
