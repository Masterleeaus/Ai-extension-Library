<?php

declare(strict_types=1);
namespace App\Extensions\SystemAIChatSkills\System\Services;
use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use App\Extensions\SystemAIChatSkills\System\Models\SkillVersion;
use App\Extensions\SystemAIChatSkills\System\Models\SkillVersionResource;
use App\Extensions\SystemAIChatSkills\System\Support\SkillVersionStatus;
use Illuminate\Support\Facades\DB;
final class SkillStatsService
{
    public function getOverviewStats(): array{return ['total_skills'=>Skill::count(),'active_skills'=>Skill::active()->count(),'total_versions'=>SkillVersion::count(),'published_versions'=>SkillVersion::published()->count(),'total_users'=>DB::table('user_skills')->distinct()->count('user_id'),'total_companies'=>DB::table('company_skills')->distinct()->count('company_id'),'verified_skills'=>Skill::where('verification_status','verified')->count()];}
    public function getUsageStats(int $skillId): array{$skill=Skill::find($skillId);return $skill?['user_count'=>$skill->users()->count(),'company_count'=>$skill->companyInstallations()->count(),'version_count'=>$skill->versions()->count(),'auto_use_count'=>DB::table('user_skills')->where('skill_id',$skillId)->where('auto_use',true)->count(),'latest_version'=>$skill->effectiveVersion()?->version]:[];}
    public function getTopSkills(int $limit=10): array{return Skill::withCount(['users','companyInstallations'])->orderByDesc('users_count')->orderByDesc('company_installations_count')->limit(max(1,min($limit,100)))->get()->map(fn(Skill $s)=>['id'=>$s->id,'name'=>$s->name,'slug'=>$s->slug,'users'=>$s->users_count,'companies'=>$s->company_installations_count,'total'=>$s->users_count+$s->company_installations_count])->all();}
    public function getVersionDistribution(): array{$out=[];foreach(SkillVersionStatus::ALL as $status)$out[$status]=SkillVersion::where('lifecycle_status',$status)->count();return $out;}
    public function getGrowthStats(int $days=30): array{$days=max(1,min($days,3650));$start=now()->subDays($days);$skills=Skill::where('created_at','>=',$start)->selectRaw('DATE(created_at) as date, count(*) as count')->groupByRaw('DATE(created_at)')->orderBy('date')->get();$versions=SkillVersion::where('created_at','>=',$start)->selectRaw('DATE(created_at) as date, count(*) as count')->groupByRaw('DATE(created_at)')->orderBy('date')->get();return ['skills'=>$skills->toArray(),'versions'=>$versions->toArray(),'total_new_skills'=>(int)$skills->sum('count'),'total_new_versions'=>(int)$versions->sum('count'),'avg_skills_per_day'=>round((float)($skills->avg('count')??0),2),'avg_versions_per_day'=>round((float)($versions->avg('count')??0),2)];}
    public function getStorageStats(): array{$total=(int)SkillVersionResource::sum('size');$count=SkillVersionResource::count();return ['total_bytes'=>$total,'formatted'=>$this->formatBytes($total),'file_count'=>$count,'avg_file_size'=>$count>0?round($total/$count,2):0];}
    private function formatBytes(int $bytes,int $precision=2): string{$units=['B','KB','MB','GB','TB'];$size=(float)$bytes;$i=0;while($size>=1024&&$i<count($units)-1){$size/=1024;$i++;}return round($size,$precision).' '.$units[$i];}
}
