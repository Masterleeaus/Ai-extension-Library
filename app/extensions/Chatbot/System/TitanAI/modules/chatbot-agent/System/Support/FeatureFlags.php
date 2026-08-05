<?php

declare(strict_types=1);

namespace App\Extensions\ChatbotAgent\System\Support;
class FeatureFlags{public static function enabled(string $flag,bool $default=false):bool{return (bool)config('chatbot-agent.feature_flags.'.$flag,config('platform-quality.feature_flags.'.$flag,$default));}}
