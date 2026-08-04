<?php

declare(strict_types=1);
namespace App\Extensions\TitanAIGovernance\System\Personas;
use App\Extensions\TitanAIGovernance\System\Models\PersonaAssignment;
use Illuminate\Support\Str;
final class PersonaAssignmentService {
 public function assign(int $tenantId,?int $userId,string $contextType,?string $contextId,array $definition,int $actorId): PersonaAssignment { PersonaAssignment::query()->where('tenant_id',$tenantId)->where('user_id',$userId)->where('context_type',$contextType)->where('context_id',$contextId)->update(['active'=>false]); return PersonaAssignment::query()->create(['uuid'=>(string)Str::uuid(),'tenant_id'=>$tenantId,'user_id'=>$userId,'context_type'=>$contextType,'context_id'=>$contextId,'name'=>(string)$definition['name'],'definition'=>$definition,'active'=>true,'assigned_by'=>$actorId]); }
 public function resolve(int $tenantId,?int $userId,string $contextType,?string $contextId): ?PersonaAssignment { return PersonaAssignment::query()->where('tenant_id',$tenantId)->where('active',true)->where(fn($q)=>$q->whereNull('user_id')->orWhere('user_id',$userId))->where('context_type',$contextType)->where(fn($q)=>$q->whereNull('context_id')->orWhere('context_id',$contextId))->latest('id')->first(); }
}
