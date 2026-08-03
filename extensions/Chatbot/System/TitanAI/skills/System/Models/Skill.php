<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Models;

use App\Extensions\SystemAIChatSkills\System\Services\SkillReleaseService;
use App\Extensions\SystemAIChatSkills\System\Support\SkillOwnershipType;
use App\Extensions\SystemAIChatSkills\System\Support\SkillVisibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Skill extends Model
{
    protected $fillable = [
        'user_id', 'company_id', 'ownership_type', 'visibility', 'created_by', 'managed_by',
        'scope_key', 'name', 'slug', 'description', 'instructions', 'bundled_resources',
        'source', 'source_url', 'is_public', 'status', 'version', 'content_hash', 'package_hash',
        'verification_status', 'metadata', 'current_version_id', 'latest_published_version_id',
    ];

    protected $casts = [
        'company_id' => 'integer', 'created_by' => 'integer', 'managed_by' => 'integer',
        'is_public' => 'boolean', 'bundled_resources' => 'array', 'metadata' => 'array',
        'current_version_id' => 'integer', 'latest_published_version_id' => 'integer',
    ];

    private const CACHE_TTL = 3600;

    protected static function booted(): void
    {
        static::saving(function (Skill $skill): void {
            $skill->normaliseOwnership();
            $skill->version = $skill->version ?: '1.0.0';
            $skill->verification_status ??= 'unverified';
            if (blank($skill->slug)) {
                $skill->slug = self::uniqueSlug($skill->name, $skill->user_id, $skill->company_id, $skill->ownership_type);
            }
        });

        static::created(function (Skill $skill): void {
            if (Schema::hasTable('skill_versions') && ! $skill->versions()->exists()) {
                app(SkillReleaseService::class)->createInitialPublishedRelease($skill);
            }
            $skill->clearCache();
        });
        static::updated(fn (Skill $skill) => $skill->clearCache());
        static::deleted(fn (Skill $skill) => $skill->clearCache());
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function managedBy(): BelongsTo { return $this->belongsTo(User::class, 'managed_by'); }
    public function companyInstallations(): HasMany { return $this->hasMany(CompanySkill::class); }
    public function versions(): HasMany { return $this->hasMany(SkillVersion::class); }
    public function currentVersion(): BelongsTo { return $this->belongsTo(SkillVersion::class, 'current_version_id'); }
    public function latestPublishedVersion(): BelongsTo { return $this->belongsTo(SkillVersion::class, 'latest_published_version_id'); }
    public function effectiveVersion(): ?SkillVersion { return $this->currentVersion ?: $this->latestPublishedVersion; }
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skills')->using(UserSkill::class)
            ->withPivot('auto_use', 'skill_version_id', 'update_policy')->withTimestamps();
    }

    public function scopePublic(Builder $query): Builder
    { return $query->where('ownership_type', SkillOwnershipType::PLATFORM)->where('visibility', SkillVisibility::PLATFORM_PUBLIC); }
    public function scopePersonal(Builder $query, int $userId): Builder
    { return $query->where('ownership_type', SkillOwnershipType::USER)->where('user_id', $userId)->where('visibility', SkillVisibility::PRIVATE_USER); }
    public function scopeCompanyVisible(Builder $query, int $companyId, bool $includePrivate = false): Builder
    {
        return $query->where('company_id', $companyId)->where('ownership_type', SkillOwnershipType::COMPANY)
            ->whereIn('visibility', $includePrivate ? [SkillVisibility::COMPANY_PRIVATE, SkillVisibility::COMPANY_SHARED] : [SkillVisibility::COMPANY_SHARED]);
    }
    public function scopeActive(Builder $query): Builder { return $query->where('status', 'active'); }
    public function scopeSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);
        if ($search === '') return $query;
        $like = '%' . addcslashes($search, '%_\\') . '%';
        return $query->where(fn (Builder $q) => $q->where('name', 'LIKE', $like)->orWhere('description', 'LIKE', $like)->orWhere('slug', 'LIKE', $like));
    }
    public function scopeInstalledForUser(Builder $query, int $userId): Builder
    { return $query->whereHas('users', fn (Builder $q) => $q->where('users.id', $userId)); }

    public function storagePath(): string
    {
        $owner = match ($this->ownership_type) {
            SkillOwnershipType::COMPANY => 'company-' . (string) $this->company_id,
            SkillOwnershipType::USER => (string) $this->user_id,
            default => 'public',
        };
        return "skills/{$owner}/{$this->slug}";
    }
    public function absoluteStoragePath(): string { return storage_path('app/' . $this->storagePath()); }

    public static function uniqueSlug(string $name, ?int $userId = null, ?int $companyId = null, ?string $ownershipType = null): string
    {
        $base = Str::slug($name) ?: 'skill';
        $ownershipType ??= $companyId !== null ? SkillOwnershipType::COMPANY : ($userId !== null ? SkillOwnershipType::USER : SkillOwnershipType::PLATFORM);
        $scopeKey = self::makeScopeKey($ownershipType, $userId, $companyId);
        $slug = $base;
        $counter = 1;
        while (static::query()->where('slug', $slug)->where('scope_key', $scopeKey)->exists()) {
            $slug = $base . '-' . $counter++;
        }
        return $slug;
    }

    public static function makeScopeKey(string $ownershipType, ?int $userId, ?int $companyId): string
    {
        return match ($ownershipType) {
            SkillOwnershipType::USER => 'user:' . (string) $userId,
            SkillOwnershipType::COMPANY => 'company:' . (string) $companyId,
            default => 'platform:0',
        };
    }

    public static function getCached(int $id): ?self
    { return Cache::remember("skills:model:{$id}", self::CACHE_TTL, fn () => self::find($id)); }
    public static function clearSkillCache(int $id): void { Cache::forget("skills:model:{$id}"); }
    public function clearCache(): void
    {
        if ($this->getKey() !== null) self::clearSkillCache((int) $this->getKey());
        Cache::increment('skills:list-generation');
    }

    /** @return array<string,mixed> */
    public static function parseSkillMd(string $content): array
    {
        $parsed = [
            'name' => '', 'description' => '', 'instructions' => '', 'bundled_resources' => null,
            'version' => '1.0.0', 'metadata' => [],
            'inputs' => ['type' => 'object', 'properties' => []], 'outputs' => ['type' => 'object'],
            'tools' => ['allowed' => [], 'required' => []], 'capabilities' => [],
        ];
        if (! preg_match('/\A---\s*\R(.*?)\R---\s*\R(.*)\z/s', $content, $matches)) {
            $parsed['instructions'] = trim($content);
            $parsed['name'] = preg_match('/^#\s*(.+)$/m', $content, $heading) ? trim($heading[1]) : 'Unnamed Skill';
            return $parsed;
        }
        try { $frontmatter = Yaml::parse($matches[1]); } catch (ParseException) { $parsed['instructions'] = trim($content); $parsed['name'] = 'Unnamed Skill'; return $parsed; }
        if (! is_array($frontmatter)) { $parsed['instructions'] = trim($content); $parsed['name'] = 'Unnamed Skill'; return $parsed; }
        $parsed['name'] = self::extractString($frontmatter, 'name', 'Unnamed Skill') ?: 'Unnamed Skill';
        $parsed['description'] = self::extractString($frontmatter, 'description');
        $parsed['instructions'] = trim($matches[2]);
        $parsed['version'] = self::extractString($frontmatter, 'version', '1.0.0');
        $parsed['metadata'] = self::extractArray($frontmatter, 'metadata');
        $contract = self::extractArray($parsed['metadata'], 'skill_contract');
        $parsed['inputs'] = self::extractArray($frontmatter, 'inputs', self::extractArray($contract, 'inputs', $parsed['inputs']));
        $parsed['outputs'] = self::extractArray($frontmatter, 'outputs', self::extractArray($contract, 'outputs', $parsed['outputs']));
        $parsed['tools'] = self::extractArray($frontmatter, 'tools', self::extractArray($contract, 'tools', $parsed['tools']));
        $parsed['capabilities'] = self::extractArray($frontmatter, 'capabilities', self::extractArray($contract, 'capabilities'));
        $parsed['bundled_resources'] = isset($frontmatter['bundled_resources']) && is_array($frontmatter['bundled_resources']) ? $frontmatter['bundled_resources'] : null;
        $parsed['metadata']['skill_contract'] = array_intersect_key($parsed, array_flip(['inputs','outputs','tools','capabilities']));
        return $parsed;
    }

    private static function extractString(array $data, string $key, string $default = ''): string
    { $value = $data[$key] ?? $default; return is_string($value) ? trim($value) : $default; }
    private static function extractArray(array $data, string $key, array $default = []): array
    { $value = $data[$key] ?? $default; return is_array($value) ? $value : $default; }

    private function normaliseOwnership(): void
    {
        if (! Schema::hasColumn($this->getTable(), 'ownership_type')) return;
        $this->ownership_type = $this->ownership_type ?: ($this->company_id !== null ? SkillOwnershipType::COMPANY : ($this->user_id !== null ? SkillOwnershipType::USER : SkillOwnershipType::PLATFORM));
        $this->visibility = $this->visibility ?: match ($this->ownership_type) {
            SkillOwnershipType::USER => SkillVisibility::PRIVATE_USER,
            SkillOwnershipType::COMPANY => SkillVisibility::COMPANY_PRIVATE,
            default => $this->is_public ? SkillVisibility::PLATFORM_PUBLIC : SkillVisibility::PLATFORM_PRIVATE,
        };
        $this->scope_key = self::makeScopeKey($this->ownership_type, $this->user_id !== null ? (int)$this->user_id : null, $this->company_id !== null ? (int)$this->company_id : null);
        $this->created_by ??= $this->user_id;
        $this->managed_by ??= $this->created_by;
        $this->is_public = $this->visibility === SkillVisibility::PLATFORM_PUBLIC;
    }

    public function getUsageStats(): array
    {
        return ['total_users'=>$this->users()->count(), 'total_companies'=>$this->companyInstallations()->count(), 'total_versions'=>$this->versions()->count(), 'latest_version'=>$this->effectiveVersion()?->version, 'is_active'=>$this->status === 'active', 'is_public'=>(bool)$this->is_public];
    }

    public function isInstallableBy(?int $userId, ?int $companyId = null): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->ownership_type === SkillOwnershipType::PLATFORM) return $this->visibility === SkillVisibility::PLATFORM_PUBLIC;
        if ($this->ownership_type === SkillOwnershipType::USER) return $userId !== null && (int)$this->user_id === $userId;
        return $companyId !== null && (int)$this->company_id === $companyId && in_array($this->visibility, [SkillVisibility::COMPANY_PRIVATE, SkillVisibility::COMPANY_SHARED], true);
    }
}
