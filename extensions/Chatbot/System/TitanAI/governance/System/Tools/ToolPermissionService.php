<?php

declare(strict_types=1);
namespace App\Extensions\TitanAIGovernance\System\Tools;
use App\Extensions\TitanAIGovernance\System\Models\ToolPermission;
final class ToolPermissionService {
 public function set(int $tenantId,string $subjectType,string $subjectId,string $tool,bool $allowed,array $constraints,int $actorId): ToolPermission { return ToolPermission::query()->updateOrCreate(['tenant_id'=>$tenantId,'subject_type'=>$subjectType,'subject_id'=>$subjectId,'tool_name'=>$tool],['allowed'=>$allowed,'constraints'=>$constraints,'updated_by'=>$actorId]); }
 public function allows(int $tenantId,string $subjectType,string $subjectId,string $tool): bool { $p=ToolPermission::query()->where('tenant_id',$tenantId)->where('subject_type',$subjectType)->where('subject_id',$subjectId)->where('tool_name',$tool)->first(); return $p ? (bool)$p->allowed : false; }
}
