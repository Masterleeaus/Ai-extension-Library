<?php
namespace App\Extensions\CustomerTags\System\Support;
class FeatureFlags{public static function enabled(string $flag,bool $default=false):bool{return (bool)config('customer-tags.feature_flags.'.$flag,config('platform-quality.feature_flags.'.$flag,$default));}}
