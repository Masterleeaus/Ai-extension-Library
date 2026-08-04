<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

/**
 * Canonical metadata overlay for Tier 3 agents.
 * Keeps legacy class names stable while exposing a consistent executable contract.
 */
final class Tier3AgentCapabilityCatalog
{
    /** @return array<string,mixed> */
    public function enrich(string $agentId, array $definition): array
    {
        $profile = $this->profile($agentId);

        return array_replace_recursive($definition, [
            'domain' => $profile['domain'],
            'capability_category' => $profile['category'],
            'triggers' => $profile['triggers'],
            'follow_up_chain' => $profile['follow_ups'],
            'execution_mode' => 'governed',
            'timeout' => 30,
            'retry_count' => 3,
            'idempotency_required' => true,
            'audit_required' => true,
            'delegates_to' => 'tier_4_tools',
            'operational_authority' => 'workcore_api_only',
            'field_service' => [
                'offline_safe' => $profile['offline_safe'],
                'realtime_events' => $profile['realtime_events'],
                'requires_human_approval' => $profile['approval'],
            ],
        ]);
    }

    /** @return array{domain:string,category:string,triggers:list<string>,follow_ups:array<string,array{condition:string}>,offline_safe:bool,realtime_events:list<string>,approval:bool} */
    public function profile(string $agentId): array
    {
        $domain = $this->domain($agentId);
        $approval = $this->requiresApproval($agentId);

        return [
            'domain' => $domain,
            'category' => str_replace('.', '_', $domain),
            'triggers' => $this->triggers($agentId),
            'follow_ups' => $this->followUps($agentId),
            'offline_safe' => ! in_array($agentId, $this->onlineOnly(), true),
            'realtime_events' => $this->realtimeEvents($agentId),
            'approval' => $approval,
        ];
    }

    private function domain(string $id): string
    {
        return match (true) {
            str_contains($id, 'customer'), str_contains($id, 'loyalty'), str_contains($id, 'review') => 'customers',
            str_contains($id, 'quote'), str_contains($id, 'lead'), str_contains($id, 'follow-up') => 'sales',
            str_contains($id, 'job'), str_contains($id, 'inspection'), str_contains($id, 'evidence'), str_contains($id, 'checklist') => 'jobs',
            str_contains($id, 'cleaner'), str_contains($id, 'technician'), str_contains($id, 'workforce') => 'workforce',
            str_contains($id, 'invoice'), str_contains($id, 'payment'), str_contains($id, 'pricing') => 'finance',
            str_contains($id, 'stock'), str_contains($id, 'inventory'), str_contains($id, 'equipment'), str_contains($id, 'purchase-order') => 'inventory',
            str_contains($id, 'route'), str_contains($id, 'dispatch'), str_contains($id, 'resource'), str_contains($id, 'reschedule') => 'operations',
            str_contains($id, 'performance'), str_contains($id, 'analysis'), str_contains($id, 'audit'), str_contains($id, 'summary'), str_contains($id, 'quality') => 'analytics',
            str_contains($id, 'blog'), str_contains($id, 'social'), str_contains($id, 'image'), str_contains($id, 'photo') => 'content',
            str_contains($id, 'message'), str_contains($id, 'notification'), str_contains($id, 'conversation') => 'communications',
            str_contains($id, 'maintenance'), str_contains($id, 'emergency'), str_contains($id, 'incident') => 'maintenance',
            default => 'operations',
        };
    }

    /** @return list<string> */
    private function triggers(string $id): array
    {
        return match ($id) {
            'create-invoice-agent' => ['job.completed'],
            'request-review-agent' => ['job.completed', 'invoice.paid'],
            'send-payment-reminder-agent' => ['invoice.overdue'],
            'mark-job-complete-agent' => ['job.evidence_ready'],
            'assign-cleaner-agent' => ['job.created', 'job.rescheduled'],
            'automated-reschedule-agent' => ['technician.unavailable', 'job.conflict'],
            'predictive-maintenance-agent' => ['equipment.risk_detected'],
            'customer-notification-agent' => ['job.status_changed', 'technician.en_route'],
            default => [],
        };
    }

    /** @return array<string,array{condition:string}> */
    private function followUps(string $id): array
    {
        return match ($id) {
            'create-customer-agent' => [
                'apply-customer-tag-agent' => ['condition' => 'on_success'],
                'send-customer-message-agent' => ['condition' => 'on_completion'],
            ],
            'accept-quote-agent' => [
                'book-job-agent' => ['condition' => 'on_success'],
                'send-customer-message-agent' => ['condition' => 'on_completion'],
            ],
            'book-job-agent' => [
                'assign-cleaner-agent' => ['condition' => 'on_success'],
                'customer-notification-agent' => ['condition' => 'on_completion'],
            ],
            'mark-job-complete-agent' => [
                'create-invoice-agent' => ['condition' => 'on_success'],
                'request-review-agent' => ['condition' => 'on_completion'],
            ],
            'record-payment-agent' => [
                'send-customer-message-agent' => ['condition' => 'on_success'],
            ],
            'record-incident-agent' => [
                'create-follow-up-agent' => ['condition' => 'on_success'],
            ],
            default => [],
        };
    }

    /** @return list<string> */
    private function realtimeEvents(string $id): array
    {
        return match (true) {
            str_contains($id, 'dispatch'), str_contains($id, 'route'), str_contains($id, 'assign'), str_contains($id, 'reschedule') => ['dispatch.updated', 'job.status'],
            str_contains($id, 'message'), str_contains($id, 'notification') => ['communication.sent', 'communication.delivery'],
            str_contains($id, 'job'), str_contains($id, 'inspection'), str_contains($id, 'evidence') => ['job.status', 'job.timeline'],
            default => [],
        };
    }

    private function requiresApproval(string $id): bool
    {
        foreach (['payment', 'invoice', 'pricing', 'payroll', 'cancel', 'publish', 'delete', 'merge', 'emergency'] as $term) {
            if (str_contains($id, $term)) return true;
        }
        return false;
    }

    /** @return list<string> */
    private function onlineOnly(): array
    {
        return [
            'publish-blog-post-agent',
            'publish-social-post-agent',
            'record-payment-agent',
            'send-invoice-agent',
            'send-quote-agent',
            'send-customer-message-agent',
            'customer-notification-agent',
        ];
    }
}
