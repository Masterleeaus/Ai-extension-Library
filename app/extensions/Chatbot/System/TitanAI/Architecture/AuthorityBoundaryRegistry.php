<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Architecture;

final class AuthorityBoundaryRegistry
{
    /** @return array<string,array<string,mixed>> */
    public function all(): array
    {
        return [
            'host' => [
                'owns' => ['authentication','users','companies','memberships','active_company_context','realtime','queues','notifications','extension_registry'],
                'must_not_delegate' => ['tenant_authority','identity_authority','permission_authority'],
            ],
            'chatbot' => [
                'owns' => ['external_conversations','customer_pwa','field_worker_pwa','channel_presentation','offline_conversation_state'],
                'must_not_own' => ['operational_business_records','finance_ledger','host_identity'],
            ],
            'titan_zero' => [
                'owns' => ['intent','planning','delegation','governance','memory','tool_selection'],
                'must_not_own' => ['operational_business_records'],
            ],
            'workcore' => [
                'owns' => ['operational_records','business_rules','transactions','domain_events','business_permissions','audit_history'],
                'write_boundary' => 'workcore_api_only',
            ],
            'titan_money' => [
                'owns' => ['operational_finance_lifecycle','settlement','reconciliation'],
                'separate_from' => ['magicai_platform_billing'],
            ],
            'extensions' => [
                'owns' => ['optional_capabilities'],
                'must_not_replace' => ['identity','tenancy','permissions','messaging_authority','workcore_authority'],
            ],
        ];
    }

    /** @return list<string> */
    public function violations(array $declaredOwnership): array
    {
        $violations = [];
        foreach ($declaredOwnership as $component => $claims) {
            if (! is_array($claims)) continue;
            foreach ((array) ($claims['owns'] ?? []) as $claim) {
                if ($component !== 'workcore' && in_array($claim, ['operational_business_records','business_transactions'], true)) {
                    $violations[] = "{$component} illegally claims WorkCore authority [{$claim}]";
                }
                if ($component !== 'host' && in_array($claim, ['authentication','tenant_authority','permission_authority'], true)) {
                    $violations[] = "{$component} illegally claims host authority [{$claim}]";
                }
            }
        }
        return $violations;
    }
}
