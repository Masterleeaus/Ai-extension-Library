<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$controllerPath = $root . '/app/extensions/CreativeSuite/System/Http/Controllers/CreativeSuiteAIController.php';

function failCreativeSuiteAITaskOwnershipContract(string $message): never
{
    fwrite(STDERR, "CreativeSuite AI task ownership contract failed: {$message}\n");
    exit(1);
}

$controller = file_get_contents($controllerPath);
if ($controller === false) {
    failCreativeSuiteAITaskOwnershipContract('unable to read CreativeSuiteAIController.php');
}

if (! preg_match('/public function status\(int \$id\): JsonResponse\n    \{(.*?)\n    \}/s', $controller, $matches)) {
    failCreativeSuiteAITaskOwnershipContract('unable to isolate status()');
}

$statusMethod = $matches[1];

if (str_contains($statusMethod, 'UserOpenai::findOrFail($id)')) {
    failCreativeSuiteAITaskOwnershipContract('status() still performs a global task lookup');
}

if (! str_contains($statusMethod, 'UserOpenai::query()')) {
    failCreativeSuiteAITaskOwnershipContract('status() does not use an explicit scoped task query');
}

if (! str_contains($statusMethod, "where('user_id', Auth::id())")) {
    failCreativeSuiteAITaskOwnershipContract('task lookup is not constrained to the authenticated owner');
}

if (! str_contains($statusMethod, '->findOrFail($id)')) {
    failCreativeSuiteAITaskOwnershipContract('missing or inaccessible task IDs do not fail closed');
}

$ownershipPosition = strpos($statusMethod, "where('user_id', Auth::id())");
$providerPollPosition = strpos($statusMethod, 'FalAIService::check(');

if ($ownershipPosition === false || ($providerPollPosition !== false && $ownershipPosition > $providerPollPosition)) {
    failCreativeSuiteAITaskOwnershipContract('ownership is not established before provider polling');
}

echo "CreativeSuite AI task ownership contract passed.\n";
