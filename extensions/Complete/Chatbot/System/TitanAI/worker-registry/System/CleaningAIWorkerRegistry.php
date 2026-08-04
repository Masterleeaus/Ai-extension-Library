<?php

declare(strict_types=1);

namespace App\Services\AI\Registry;

final class CleaningAIWorkerRegistry
{
    /** @return array<int,list<class-string>> */
    public static function byTier(): array
    {
        $tiers = [
            1 => self::classes('App\\Services\\AI\\Tier1\\Managers', dirname(__DIR__, 2).'/tier1/managers'),
            2 => array_merge(
                self::classes('App\\Services\\AI\\Tier2\\Assistants', dirname(__DIR__, 2).'/tier2/assistants'),
                self::verticalSpecialists(),
            ),
            3 => self::classes('App\\Services\\AI\\Tier3\\Agents\\Actions', dirname(__DIR__, 2).'/tier3/Actions'),
        ];
        foreach ($tiers as &$classes) {
            $classes = array_values(array_filter(array_unique($classes), 'class_exists'));
            sort($classes);
        }
        return $tiers;
    }

    /** @return list<array<string,mixed>> */
    public static function definitions(): array
    {
        $definitions = [];
        foreach (self::byTier() as $workers) {
            foreach ($workers as $worker) {
                if (method_exists($worker, 'definition')) $definitions[] = $worker::definition();
            }
        }
        return $definitions;
    }

    /** Compatibility aliases do not increase canonical worker counts. */
    public static function aliases(): array
    {
        return [
            'assign-technician-agent' => 'assign-cleaner-agent',
            'intelligent-scheduler-agent' => 'automated-reschedule-agent',
            'route-optimization-agent' => 'optimise-route-agent',
            'technician-availability-agent' => 'workforce-availability-agent',
            'job-completion-verification-agent' => 'mark-job-complete-agent',
            'service-inventory-agent' => 'update-stock-agent',
            'equipment-reservation-agent' => 'allocate-equipment-agent',
            'customer-communication-agent' => 'send-customer-message-agent',
            'service-reminder-agent' => 'send-payment-reminder-agent',
            'safety-incident-reporting-agent' => 'record-incident-agent',
            'service-performance-analytics-agent' => 'performance-metrics-agent',
        ];
    }

    public static function canonicalCounts(): array
    {
        $tiers = self::byTier();
        return [1 => count($tiers[1] ?? []), 2 => count($tiers[2] ?? []), 3 => count($tiers[3] ?? [])];
    }

    /** @return list<class-string> */
    private static function classes(string $namespace, string $directory): array
    {
        $classes = [];
        foreach (glob($directory.'/*.php') ?: [] as $file) {
            $classes[] = $namespace.'\\'.basename($file, '.php');
        }
        return $classes;
    }

    /** @return list<class-string> */
    private static function verticalSpecialists(): array
    {
        $root = dirname(__DIR__, 2).'/tier2/specialists/verticals';
        $map = ['residential'=>'Residential','commercial'=>'Commercial','pool'=>'Pool','pressure'=>'Pressure','car'=>'Car'];
        $classes = [];
        foreach ($map as $folder => $namespace) {
            $classes = array_merge($classes, self::classes('App\\Services\\AI\\Tier2\\'.$namespace, $root.'/'.$folder));
        }
        return $classes;
    }
}
