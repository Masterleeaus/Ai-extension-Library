<?php
namespace App\Extensions\Reviews\System\Support;
class FeatureFlags{public static function enabled(string $flag,bool $default=false):bool{return (bool)config('reviews.feature_flags.'.$flag,config('platform-quality.feature_flags.'.$flag,$default));}}
