<?php

declare(strict_types=1);
namespace App\Extensions\SystemAIChatSkills\System\Policies;
use App\Extensions\SystemAIChatSkills\System\Authorization\SkillAccessContext;
use App\Extensions\SystemAIChatSkills\System\Authorization\SkillAccessEvaluator;
use App\Extensions\SystemAIChatSkills\System\Contracts\CompanyContextResolverInterface;
use App\Extensions\SystemAIChatSkills\System\Contracts\CompanySkillInstallationLookupInterface;
use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use App\Extensions\SystemAIChatSkills\System\Support\SkillOwnershipType;
use App\Extensions\SystemAIChatSkills\System\Support\SkillVisibility;
use App\Extensions\SystemAIChatSkills\System\Tenancy\CompanyContext;
use App\Extensions\SystemAIChatSkills\System\Tenancy\CompanySkillInstallationContext;
use App\Models\User;
final class SkillPolicy
{
    public function __construct(private readonly SkillAccessEvaluator $evaluator,private readonly CompanyContextResolverInterface $companyResolver,private readonly CompanySkillInstallationLookupInterface $installationLookup){}
    public function preview(?User $user,Skill $skill): bool{return $this->evaluator->preview($this->context($user,$skill));}
    public function view(User $user,Skill $skill): bool{return $this->evaluator->view($this->context($user,$skill,true));}
    public function add(User $user,Skill $skill): bool{return $this->evaluator->add($this->context($user,$skill));}
    public function remove(User $user,Skill $skill): bool{return $this->evaluator->remove($this->context($user,$skill,true));}
    public function activate(User $user,Skill $skill): bool{return $this->evaluator->activate($this->context($user,$skill,true));}
    public function update(User $user,Skill $skill): bool{return $this->evaluator->update($this->context($user,$skill));}
    public function delete(User $user,Skill $skill): bool{return $this->evaluator->delete($this->context($user,$skill));}
    public function export(User $user,Skill $skill): bool{return $this->evaluator->export($this->context($user,$skill,true));}
    public function readResource(User $user,Skill $skill): bool{return $this->evaluator->readResource($this->context($user,$skill,true));}
    public function publish(User $user,Skill $skill): bool{return $this->evaluator->publish($this->context($user,$skill));}
    public function share(User $user,Skill $skill): bool{return $this->evaluator->share($this->context($user,$skill));}
    public function manageVersions(User $user,Skill $skill): bool{return $this->evaluator->manageVersions($this->context($user,$skill));}
    public function installForCompany(User $user,Skill $skill): bool{return $this->evaluator->installForCompany($this->context($user,$skill));}
    public function removeFromCompany(User $user,Skill $skill): bool{return $this->evaluator->removeFromCompany($this->context($user,$skill));}
    public function configureCompany(User $user,Skill $skill): bool{return $this->evaluator->configureCompany($this->context($user,$skill));}
    public function viewCompanyInstallations(User $user,Skill $skill): bool{$c=$this->context($user,$skill);return $c->isAdmin||$c->isCompanyManager;}
    public function bulkAdd(User $user,array $skillIds): bool{return $this->bulkAuthorise($user,$skillIds,'add');}
    public function bulkRemove(User $user,array $skillIds): bool{return $this->bulkAuthorise($user,$skillIds,'remove');}
    private function bulkAuthorise(User $user,array $skillIds,string $ability): bool
    {
        $ids=array_values(array_unique(array_filter(array_map('intval',$skillIds),fn(int $id)=>$id>0)));
        if($ids===[])return false;
        $skills=Skill::query()->whereKey($ids)->get()->keyBy(fn(Skill $s)=>(int)$s->getKey());
        if($skills->count()!==count($ids))return false;
        foreach($ids as $id)if(! $this->{$ability}($user,$skills[$id]))return false;
        return true;
    }
    private function context(?User $user,Skill $skill,bool $includeAttachment=false): SkillAccessContext
    {
        $isAttached=false;$company=CompanyContext::none();$installation=CompanySkillInstallationContext::missing();
        if($user!==null){$company=$this->companyResolver->resolve($user);if($includeAttachment){$isAttached=$user->belongsToMany(Skill::class,'user_skills')->where('skills.id',$skill->id)->exists();}$installation=$this->installationLookup->for($skill,$company);}
        $ownershipType=$skill->ownership_type??($skill->company_id!==null?SkillOwnershipType::COMPANY:($skill->user_id!==null?SkillOwnershipType::USER:SkillOwnershipType::PLATFORM));
        $visibility=$skill->visibility??match($ownershipType){SkillOwnershipType::USER=>SkillVisibility::PRIVATE_USER,SkillOwnershipType::COMPANY=>SkillVisibility::COMPANY_PRIVATE,default=>$skill->is_public?SkillVisibility::PLATFORM_PUBLIC:SkillVisibility::PLATFORM_PRIVATE};
        return new SkillAccessContext(isAdmin:$user!==null&&$user->isAdmin(),actorId:$user!==null?(int)$user->getKey():null,ownerId:$skill->user_id!==null?(int)$skill->user_id:null,isPublic:(bool)$skill->is_public,isActive:$skill->status==='active',isAttached:$isAttached,isSeeded:$skill->source==='seeded',actorCompanyId:$company->companyId,skillCompanyId:$skill->company_id!==null?(int)$skill->company_id:null,ownershipType:$ownershipType,visibility:$visibility,isCompanyManager:$company->isManager,isCompanyInstalled:$installation->installed,isCompanyInstallationEnabled:$installation->enabled,isCompanyRoleAllowed:$installation->roleAllowed);
    }
}
