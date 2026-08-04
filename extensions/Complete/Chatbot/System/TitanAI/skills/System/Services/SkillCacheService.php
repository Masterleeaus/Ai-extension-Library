<?php

declare(strict_types=1);
namespace App\Extensions\SystemAIChatSkills\System\Services;
use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use App\Extensions\SystemAIChatSkills\System\Models\SkillVersion;
use Illuminate\Support\Facades\Cache;
final class SkillCacheService
{
    private const TTL=3600;
    public function getSkill(int $id): ?Skill{return Cache::remember("skills:full:{$id}",self::TTL,fn()=>Skill::with(['currentVersion','latestPublishedVersion','versions'])->find($id));}
    public function getVersion(int $id): ?SkillVersion{return Cache::remember("skills:version-full:{$id}",self::TTL,fn()=>SkillVersion::with(['skill','resources'])->find($id));}
    public function getUserSkills(int $userId,string $tab='all',string $search=''): array
    {
        $generation=(int)Cache::get('skills:list-generation',1);$key='skills:user-list:'.$generation.':'.$userId.':'.$tab.':'.hash('sha256',$search);
        return Cache::remember($key,self::TTL,function()use($userId,$tab,$search){$q=Skill::query()->with(['currentVersion','creator'])->where(fn($x)=>$x->where('user_id',$userId)->orWhere('is_public',true));if($tab==='personal')$q->where('user_id',$userId);elseif($tab==='public')$q->where('is_public',true);if(trim($search)!=='')$q->search($search);return $q->get()->toArray();});
    }
    public function clearSkill(int $id): void{foreach(["skills:full:{$id}","skills:model:{$id}"]as$key)Cache::forget($key);Cache::increment('skills:list-generation');}
    public function clearVersion(int $id): void{foreach(["skills:version:{$id}","skills:version-full:{$id}"]as$key)Cache::forget($key);Cache::increment('skills:list-generation');}
    public function warmSkill(int $id): void{$skill=$this->getSkill($id);if($skill!==null)foreach($skill->versions as $version)$this->getVersion((int)$version->getKey());}
    public function getStats(): array{return ['ttl'=>self::TTL,'prefix'=>'skills:','driver'=>config('cache.default'),'list_generation'=>(int)Cache::get('skills:list-generation',1)];}
}
