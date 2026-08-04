<?php

declare(strict_types=1);
namespace App\Extensions\TitanAIGovernance\System\Memory;
use App\Extensions\TitanAIGovernance\System\Models\GovernedMemory;
final class MemoryDecayService {
 public function run(?int $tenantId=null): array { $q=GovernedMemory::query(); if($tenantId!==null)$q->where('tenant_id',$tenantId); $expired=(clone $q)->whereNotNull('expires_at')->where('expires_at','<=',now())->update(['status'=>'expired','authoritative'=>false]); $cutoff=now()->subDays((int)config('titan-ai-governance.memory.decay_after_days',90)); $decayed=(clone $q)->whereIn('status',['active','approved'])->where('importance','<=',2)->where('last_reinforced_at','<',$cutoff)->update(['status'=>'decayed','authoritative'=>false]); return compact('expired','decayed'); }
}
