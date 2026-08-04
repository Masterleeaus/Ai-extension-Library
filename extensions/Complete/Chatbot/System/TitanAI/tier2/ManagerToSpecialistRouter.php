<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2;

use RuntimeException;

final class ManagerToSpecialistRouter
{
    /** @param array<string,mixed> $managerOutput @param array<string,mixed> $context */
    public static function route(array $managerOutput, array $context): array
    {
        $vertical = strtolower((string) ($context['vertical'] ?? 'residential'));
        $intent = (string) ($context['intent'] ?? 'general_inquiry');
        $specialist = self::selectSpecialist($vertical, $intent);
        if (! method_exists($specialist, 'process')) throw new RuntimeException('Selected specialist cannot process requests.');
        return $specialist->process($context + ['manager_output'=>$managerOutput]);
    }

    private static function selectSpecialist(string $vertical, string $intent): object
    {
        $verticals = ['residential'=>'Residential','commercial'=>'Commercial','pool'=>'Pool','pressure'=>'Pressure','car'=>'Car'];
        $prefix = $verticals[$vertical] ?? 'Residential';
        $class = 'App\\Services\\AI\\Tier2\\'.$prefix.'\\'.$prefix.self::specialistSuffix($intent);
        if (! class_exists($class)) {
            $class = 'App\\Services\\AI\\Tier2\\Residential\\ResidentialJobSchedulingSpecialist';
        }
        if (! class_exists($class)) throw new RuntimeException('No Tier 2 specialist is available.');
        return new $class();
    }

    private static function specialistSuffix(string $intent): string
    {
        return match ($intent) {
            'schedule_job','book_job','reschedule_job','setup_recurring' => 'JobSchedulingSpecialist',
            'assign_crew','assign_cleaner','request_specific_crew' => 'CrewDispatchSpecialist',
            'check_status','view_jobs','view_schedule' => 'JobStatusSpecialist',
            'complete_job','quality_followup' => 'JobCompletionSpecialist',
            'create_invoice','create_quote','record_payment','process_payment','apply_discount','request_refund' => 'BillingSpecialist',
            'rate_crew','view_crew_performance' => 'CrewManagementSpecialist',
            'create_customer','update_customer','customer_support','manage_account' => 'CustomerManagementSpecialist',
            default => 'JobSchedulingSpecialist',
        };
    }
}
