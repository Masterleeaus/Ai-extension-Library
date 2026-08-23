<?php

declare(strict_types=1);

namespace App\Extensions\WorkCore\System\Http\Controllers;

use App\Domains\WorkCore\System\ReadModels\ReadModelExecutor;
use App\Extensions\WorkCore\System\Navigation\WorkCoreWorkspaceCatalogue;
use App\Extensions\WorkCore\System\Navigation\WorkCoreWorkspaceManifest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class WorkspaceController
{
    public function __construct(
        private WorkCoreWorkspaceCatalogue $catalogue,
        private WorkCoreWorkspaceManifest $manifest,
        private ReadModelExecutor $reads,
    ) {}

    public function show(Request $request): View
    {
        $workspaceKey = (string) $request->route('workspace');
        $sectionKey = $request->route('section');
        $workspaceDefinition = $this->catalogue->workspace($workspaceKey);
        if ($workspaceDefinition === null) {
            abort(404, 'Unknown WorkCore workspace.');
        }
        if ($sectionKey !== null && $this->catalogue->section($workspaceKey, (string) $sectionKey) === null) {
            abort(404, 'Unknown WorkCore workspace section.');
        }

        $manifest = $this->manifest->forActiveCompany();
        $workspace = null;
        foreach ($manifest['workspaces'] as $candidate) {
            if (($candidate['key'] ?? null) === $workspaceKey) {
                $workspace = $candidate;
                break;
            }
        }
        if ($workspace === null) {
            abort(404, 'The requested WorkCore workspace is not available.');
        }

        $selected = $workspace;
        if ($sectionKey !== null) {
            $selected = null;
            foreach ($workspace['sections'] as $candidate) {
                if (($candidate['key'] ?? null) === $sectionKey) {
                    $selected = $candidate;
                    break;
                }
            }
            if ($selected === null) {
                abort(404, 'The requested WorkCore workspace section is not available.');
            }
        }

        if ($workspaceKey === 'commercial'
            && $sectionKey === 'pricing'
            && view()->exists('workcore-pricing::dashboard')) {
            $filters = $request->validate([
                'days' => ['nullable','integer','min:1','max:365'],
                'target_type' => ['nullable','string','max:80'],
                'target_reference' => ['nullable','string','max:160'],
            ]);
            $tenant = workcore_tenant();
            $analytics = $this->reads->execute(
                'workcore.pricing.analytics',
                $filters,
                (int) $tenant->companyId(),
                (int) $tenant->userId(),
            );

            return view('workcore-pricing::dashboard', [
                'analytics' => $analytics,
                'workspace' => $workspace,
                'selected' => $selected,
            ]);
        }

        return view('workcore::workspace', [
            'companyId' => $manifest['company_id'],
            'entitlementRevision' => $manifest['entitlement_revision'],
            'workspace' => $workspace,
            'sections' => $workspace['sections'],
            'selected' => $selected,
        ]);
    }
}
