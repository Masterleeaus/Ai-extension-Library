<?php

declare(strict_types=1);
namespace App\Extensions\TitanAIGovernance\System\Memory;
use App\Extensions\TitanAIGovernance\System\Models\GovernedMemory;
use Illuminate\Support\Str;
use InvalidArgumentException;
final class PersistentMemoryService {
 public function __construct(private readonly MemoryGovernanceService $governance) {}
 public function store(int $tenantId, ?int $userId, MemoryPolicy $policy, array $record, string $role, bool $approved=false): GovernedMemory {
  $data=$this->governance->validateWrite($policy,$record,$role,$approved);
  return GovernedMemory::query()->create(['uuid'=>(string)Str::uuid(),'tenant_id'=>$tenantId,'user_id'=>$userId,'scope'=>$data['scope'],'subject_type'=>$record['subject_type']??null,'subject_id'=>$record['subject_id']??null,'payload'=>$record['payload']??$record,'importance'=>$data['importance'],'status'=>$data['status']??($approved?'approved':'active'),'authoritative'=>$data['authoritative']??$approved,'reinforcement_count'=>1,'last_reinforced_at'=>now(),'approved_at'=>$approved?now():null,'expires_at'=>$data['expires_at'],'metadata'=>$record['metadata']??[]]);
 }
 public function approve(int $tenantId, string $uuid, int $approverId): GovernedMemory { $m=GovernedMemory::query()->where('tenant_id',$tenantId)->where('uuid',$uuid)->firstOrFail(); if($m->status!=='suggested') throw new InvalidArgumentException('Only suggested memory can be approved.'); $m->update(['status'=>'approved','authoritative'=>true,'approved_by'=>$approverId,'approved_at'=>now()]); return $m->refresh(); }
 public function reinforce(int $tenantId,string $uuid): GovernedMemory { $m=GovernedMemory::query()->where('tenant_id',$tenantId)->where('uuid',$uuid)->firstOrFail(); $m->increment('reinforcement_count'); $m->update(['last_reinforced_at'=>now()]); return $m->refresh(); }
}
