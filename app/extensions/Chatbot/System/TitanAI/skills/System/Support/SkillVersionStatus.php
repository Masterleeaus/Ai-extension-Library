<?php

declare(strict_types=1);
namespace App\Extensions\SystemAIChatSkills\System\Support;
final class SkillVersionStatus
{
    public const DRAFT='draft'; public const VALIDATING='validating'; public const REVIEW_REQUIRED='review_required'; public const APPROVED='approved'; public const PUBLISHED='published'; public const DEPRECATED='deprecated'; public const SUSPENDED='suspended'; public const QUARANTINED='quarantined'; public const ARCHIVED='archived';
    public const INSTALLABLE=[self::PUBLISHED,self::DEPRECATED];
    public const EDITABLE=[self::DRAFT,self::VALIDATING];
    public const ALL=[self::DRAFT,self::VALIDATING,self::REVIEW_REQUIRED,self::APPROVED,self::PUBLISHED,self::DEPRECATED,self::SUSPENDED,self::QUARANTINED,self::ARCHIVED];
    public const LABELS=[self::DRAFT=>'Draft',self::VALIDATING=>'Validating',self::REVIEW_REQUIRED=>'Review Required',self::APPROVED=>'Approved',self::PUBLISHED=>'Published',self::DEPRECATED=>'Deprecated',self::SUSPENDED=>'Suspended',self::QUARANTINED=>'Quarantined',self::ARCHIVED=>'Archived'];
    public const COLORS=[self::DRAFT=>'gray',self::VALIDATING=>'yellow',self::REVIEW_REQUIRED=>'orange',self::APPROVED=>'green',self::PUBLISHED=>'green',self::DEPRECATED=>'red',self::SUSPENDED=>'red',self::QUARANTINED=>'red',self::ARCHIVED=>'gray'];
    public const CSS_CLASSES=[self::DRAFT=>'bg-gray-100 text-gray-800',self::VALIDATING=>'bg-yellow-100 text-yellow-800',self::REVIEW_REQUIRED=>'bg-orange-100 text-orange-800',self::APPROVED=>'bg-green-100 text-green-800',self::PUBLISHED=>'bg-green-100 text-green-800',self::DEPRECATED=>'bg-red-100 text-red-800',self::SUSPENDED=>'bg-red-100 text-red-800',self::QUARANTINED=>'bg-red-100 text-red-800',self::ARCHIVED=>'bg-gray-100 text-gray-800'];
    private function __construct(){}
    public static function isInstallable(string $status): bool { return in_array($status,self::INSTALLABLE,true); }
    public static function isEditable(string $status): bool { return in_array($status,self::EDITABLE,true); }
    public static function isValid(string $status): bool { return in_array($status,self::ALL,true); }
    public static function canPublish(string $status): bool { return in_array($status,[self::DRAFT,self::APPROVED],true); }
    public static function getLabel(string $status): string { return self::LABELS[$status]??ucfirst(str_replace('_',' ',$status)); }
    public static function getColor(string $status): string { return self::COLORS[$status]??'gray'; }
    public static function getCssClass(string $status): string { return self::CSS_CLASSES[$status]??'bg-gray-100 text-gray-800'; }
    public static function getOptions(): array { return array_combine(self::ALL,array_map([self::class,'getLabel'],self::ALL)); }
    public static function getInstallableOptions(): array { return array_combine(self::INSTALLABLE,array_map([self::class,'getLabel'],self::INSTALLABLE)); }
}
