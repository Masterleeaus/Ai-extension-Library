<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Extensions\TitanAIGovernance\System\Models\{GovernedMemory, PersonaAssignment, GovernedSkill, ToolPermission, SkillEvaluation};
use Illuminate\Http\Request;

final class GovernanceConfigurationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tenantId = (string) ($user?->company_id ?? $user?->tenant_id ?? '');
        abort_if(! $user || $tenantId === '' || ! method_exists($user, 'can') || ! $user->can('govern-ai-actions'), 403);

        return view('titan-ai-governance::configuration.index', [
            'memories' => GovernedMemory::where('tenant_id', $tenantId)->latest()->limit(25)->get(),
            'personas' => PersonaAssignment::where('tenant_id', $tenantId)->where('active', true)->get(),
            'skills' => GovernedSkill::where('tenant_id', $tenantId)->get(),
            'permissions' => ToolPermission::where('tenant_id', $tenantId)->get(),
            'evaluations' => SkillEvaluation::where('tenant_id', $tenantId)->latest()->limit(25)->get(),
        ]);
    }
}
