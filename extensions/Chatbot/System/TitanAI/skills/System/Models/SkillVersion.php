<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Models;

use App\Extensions\SystemAIChatSkills\System\Packages\SkillResourceFingerprint;
use App\Extensions\SystemAIChatSkills\System\Support\SkillVersionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class SkillVersion extends Model
{
    protected $table = 'skill_versions';
    protected $fillable = ['skill_id','version','lifecycle_status','name','description','instructions','bundled_resources','metadata','content_hash','package_hash','manifest_hash','instructions_hash','source','source_url','source_repository','source_branch','source_commit_sha','imported_at','imported_by','verification_status','verification_details','verified_at','release_notes','created_by','published_by','published_at','deprecated_at','suspended_at','archived_at','based_on_version_id'];
    protected $casts = ['bundled_resources'=>'array','metadata'=>'array','verification_details'=>'array','imported_at'=>'datetime','verified_at'=>'datetime','published_at'=>'datetime','deprecated_at'=>'datetime','suspended_at'=>'datetime','archived_at'=>'datetime','created_by'=>'integer','published_by'=>'integer','imported_by'=>'integer','based_on_version_id'=>'integer'];
    private const CACHE_TTL = 3600;

    protected static function booted(): void
    {
        static::saved(fn (self $version) => $version->clearCache());
        static::deleted(fn (self $version) => $version->clearCache());
    }
    public function skill(): BelongsTo { return $this->belongsTo(Skill::class); }
    public function resources(): HasMany { return $this->hasMany(SkillVersionResource::class); }
    public function basedOn(): BelongsTo { return $this->belongsTo(self::class, 'based_on_version_id'); }
    public function createdByUser(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function publishedByUser(): BelongsTo { return $this->belongsTo(User::class, 'published_by'); }
    public function scopePublished(Builder $query): Builder { return $query->where('lifecycle_status', SkillVersionStatus::PUBLISHED); }
    public function scopeInstallable(Builder $query): Builder { return $query->whereIn('lifecycle_status', SkillVersionStatus::INSTALLABLE); }
    public function scopeDraft(Builder $query): Builder { return $query->where('lifecycle_status', SkillVersionStatus::DRAFT); }
    public function scopeVerified(Builder $query): Builder { return $query->where('verification_status', 'verified'); }
    public static function getCached(int $id): ?self { return Cache::remember("skills:version:{$id}", self::CACHE_TTL, fn()=>self::find($id)); }
    public function clearCache(): void
    {
        if ($this->getKey() !== null) Cache::forget('skills:version:' . $this->getKey());
        if ($this->skill_id !== null) Skill::clearSkillCache((int)$this->skill_id);
        Cache::increment('skills:list-generation');
    }
    public function isDraft(): bool { return $this->lifecycle_status === SkillVersionStatus::DRAFT; }
    public function isPublished(): bool { return $this->lifecycle_status === SkillVersionStatus::PUBLISHED; }
    public function isInstallable(): bool { return SkillVersionStatus::isInstallable((string)$this->lifecycle_status); }
    public function isContentMutable(): bool { return SkillVersionStatus::isEditable((string)$this->lifecycle_status); }
    public function canPublish(): bool { return SkillVersionStatus::canPublish((string)$this->lifecycle_status); }
    public function isVerified(): bool { return $this->verification_status === 'verified'; }
    public function getResourceFingerprints(): array
    { return $this->resources->map(fn (SkillVersionResource $r) => $r->fingerprint())->all(); }
    public function getResource(string $path): ?SkillVersionResource { return $this->resources()->where('path',$path)->first(); }
    public function hasResource(string $path): bool { return $this->resources()->where('path',$path)->exists(); }
    public function compareWith(self $other): array
    {
        if ((int)$this->skill_id !== (int)$other->skill_id) return ['error'=>'Versions belong to different skills'];
        $differences=[];
        foreach (['version','name','description','instructions','bundled_resources','metadata'] as $field) if ($this->{$field} !== $other->{$field}) $differences[$field]=['from'=>$this->{$field},'to'=>$other->{$field}];
        $left=$this->resources()->pluck('sha256','path')->all(); $right=$other->resources()->pluck('sha256','path')->all();
        $added=array_keys(array_diff_key($right,$left)); $removed=array_keys(array_diff_key($left,$right)); $modified=[];
        foreach ($left as $path=>$hash) if (isset($right[$path]) && ! hash_equals((string)$hash,(string)$right[$path])) $modified[]=$path;
        if ($added) $differences['resources_added']=$added; if ($removed) $differences['resources_removed']=$removed; if ($modified) $differences['resources_modified']=$modified;
        return $differences;
    }
    public function getSummary(): array
    { return ['id'=>$this->id,'version'=>$this->version,'status'=>$this->lifecycle_status,'name'=>$this->name,'description'=>$this->description,'published_at'=>$this->published_at?->toISOString(),'resource_count'=>$this->resources()->count(),'is_installable'=>$this->isInstallable(),'is_verified'=>$this->isVerified(),'verification_status'=>$this->verification_status]; }
    public function getTotalSize(): int { return (int)$this->resources()->sum('size'); }
    public function getFormattedSize(): string { return self::formatBytes($this->getTotalSize()); }
    private static function formatBytes(int $bytes): string
    { $units=['B','KB','MB','GB','TB']; $size=(float)$bytes; $i=0; while ($size>=1024 && $i<count($units)-1){$size/=1024;$i++;} return round($size,2).' '.$units[$i]; }
}
