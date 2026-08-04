<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Architecture;

final class ProjectArchitectureAuditService
{
    public function __construct(
        private readonly AuthorityBoundaryRegistry $boundaries,
        private readonly InteractionEngineContractCatalog $interaction,
    ) {}

    /** @return array<string,mixed> */
    public function report(): array
    {
        $sources = (array) config('titan_project_architecture.sources', []);
        $missing = [];
        foreach ($sources as $id => $source) {
            if (! is_array($source) || empty($source['role']) || empty($source['authority'])) {
                $missing[] = (string) $id;
            }
        }

        return [
            'status' => $missing === [] ? 'healthy' : 'incomplete',
            'canonical_authorities' => $this->boundaries->all(),
            'source_register' => $sources,
            'invalid_source_entries' => $missing,
            'interaction_engine' => [
                'contract_groups' => $this->interaction->groups(),
                'adoption_policy' => $this->interaction->adoptionPolicy(),
            ],
            'hard_rules' => [
                'workcore_is_only_operational_write_authority',
                'host_resolves_identity_tenancy_and_membership',
                'chatbot_is_a_surface_not_a_business_record_owner',
                'donor_modules_are_not_runtime_authority',
                'disabled_optional_extensions_must_not_boot_routes_jobs_or_listeners',
                'five_tier_workers_use_allowlisted_tools',
            ],
        ];
    }
}
