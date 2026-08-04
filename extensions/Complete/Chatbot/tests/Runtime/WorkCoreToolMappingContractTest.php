<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$config = $root.'/System/TitanAI/workcore-runtime/config/titan_ai_runtime.php';
$intent = file_get_contents($root.'/System/TitanAI/Runtime/IntentGateway.php');
$orchestrator = file_get_contents($root.'/System/TitanAI/Runtime/FiveTierOrchestrator.php');
$gateway = file_get_contents($root.'/System/TitanAI/governance/System/WorkCoreAccessGateway.php');

$definitions = require $config;
$map = $definitions['operation_tool_map'] ?? [];
$required = [
    'invoicing.create_invoice','invoicing.send_invoice','invoicing.apply_payment','invoicing.create_quote',
    'jobs.create_job_order','scheduling.reschedule_appointment','jobs.cancel_job','jobs.assign_to_technician',
    'field_ops.optimize_route','jobs.complete_job','crm.create_contact','crm.update_contact',
    'inventory.adjust_stock','field_ops.record_incident',
];

$failures = [];
foreach ($required as $operation) {
    if (! isset($map[$operation]) || ! is_string($map[$operation]) || $map[$operation] === '') {
        $failures[] = "missing mapping {$operation}";
    }
}
if (count($map) !== count(array_unique(array_keys($map)))) $failures[] = 'duplicate operation keys';
if (! str_contains($intent, 'function actionDefinitions')) $failures[] = 'intent definitions not auditable';
if (! str_contains($orchestrator, '(string) $decision->domain')) $failures[] = 'orchestrator does not validate domain mapping';
if (! str_contains($orchestrator, '(string) $decision->operation')) $failures[] = 'orchestrator does not validate operation mapping';
if (! str_contains($gateway, 'WorkCoreToolMappingRegistry')) $failures[] = 'gateway bypasses mapping registry';

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures).PHP_EOL);
    exit(1);
}

echo 'WorkCore tool mapping contract passed: '.count($required).' active operations mapped.'.PHP_EOL;
