<?php

declare(strict_types=1);

namespace App\Extensions\CustomerTags\System\Support;use Illuminate\Support\Facades\Log;
class ExtensionLogger{public static function info(string$message,array$context=[]):void{Log::info('[customer-tags] '.$message,$context);}public static function error(string$message,array$context=[]):void{Log::error('[customer-tags] '.$message,$context);}}
