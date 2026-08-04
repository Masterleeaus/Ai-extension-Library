<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [
    'authenticated permission snapshot reaches runtime context' => [
        $root.'/System/TitanAI/DTO/TitanAIRequest.php',
        "'available_permissions' => \$this->availablePermissions",
    ],
    'controller derives permissions from authenticated user' => [
        $root.'/System/Http/Controllers/Api/TitanAI/TitanAIExecutionController.php',
        'permissionSnapshot($user)',
    ],
    'governance forwards selected agent identity' => [
        $root.'/System/TitanAI/governance/System/Tools/GovernedToolExecutor.php',
        "'agent' => \$context['agent'] ?? null",
    ],
    'action receipts persist device identity' => [
        $root.'/System/TitanAI/governance/System/Tools/GovernedToolExecutor.php',
        "'device_id' => \$case->deviceId",
    ],
    'action receipts schema records worker chain' => [
        $root.'/System/TitanAI/governance/database/migrations/2026_07_28_000010_add_execution_context_to_titan_ai_action_receipts.php',
        "\$table->string('agent_id')",
    ],
    'execution diagnostics describe the active governed path honestly' => [
        $root.'/System/TitanAI/Runtime/AgentExecutionPathResolver.php',
        "'mode' => 'governed_workcore'",
    ],
];

$failed = [];
foreach ($checks as $name => [$file, $needle]) {
    $content = is_file($file) ? file_get_contents($file) : false;
    if ($content === false || ! str_contains($content, $needle)) $failed[] = $name;
}
if ($failed !== []) {
    fwrite(STDERR, "Failed: ".implode(', ', $failed).PHP_EOL);
    exit(1);
}
echo count($checks)." Pass 6 contract checks passed".PHP_EOL;
