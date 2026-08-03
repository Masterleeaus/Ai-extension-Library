<?php

declare(strict_types=1);
namespace App\Extensions\TitanAIGovernance\System\Skills;
use App\Extensions\TitanAIGovernance\System\Models\GovernedSkill;
use App\Extensions\TitanAIGovernance\System\Models\SkillEvaluation;
use Illuminate\Support\Str;
use InvalidArgumentException;
final class GovernedSkillService {
 public function save(int $tenantId,array $definition,int $actorId): GovernedSkill { foreach(['name','tools','maximum_risk','success_criteria','metrics'] as $key) if(!array_key_exists($key,$definition)) throw new InvalidArgumentException("Missing skill field: {$key}"); return GovernedSkill::query()->updateOrCreate(['tenant_id'=>$tenantId,'slug'=>$definition['slug']??Str::slug($definition['name'])],['uuid'=>(string)Str::uuid(),'name'=>$definition['name'],'definition'=>$definition,'active'=>(bool)($definition['active']??true),'updated_by'=>$actorId]); }
 public function evaluate(GovernedSkill $skill,string $executionId,array $observed): SkillEvaluation { $def=$skill->definition; $criteria=(array)($def['success_criteria']??[]); $passed=true; foreach($criteria as $key=>$expected){ if(($observed[$key]??null)!==$expected){$passed=false;break;} } return SkillEvaluation::query()->create(['tenant_id'=>$skill->tenant_id,'skill_id'=>$skill->id,'execution_id'=>$executionId,'passed'=>$passed,'metrics'=>$observed,'evaluated_at'=>now()]); }
}
