<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$controllerPath = $root . '/app/extensions/AdvancedImage/System/Http/Controllers/AdvancedImageStatusController.php';

function failAdvancedImageTaskOwnershipContract(string $message): never
{
    fwrite(STDERR, "AdvancedImage task ownership contract failed: {$message}\n");
    exit(1);
}

$controller = file_get_contents($controllerPath);
if ($controller === false) {
    failAdvancedImageTaskOwnershipContract('unable to read AdvancedImageStatusController.php');
}

if (! preg_match('/public function __invoke\(int \$id\): JsonResponse\n    \{(.*?)\n    \}/s', $controller, $matches)) {
    failAdvancedImageTaskOwnershipContract('unable to isolate __invoke()');
}

$statusMethod = $matches[1];

if (str_contains($statusMethod, 'UserOpenai::findOrFail($id)')) {
    failAdvancedImageTaskOwnershipContract('__invoke() still performs a global task lookup');
}

if (! str_contains($statusMethod, 'UserOpenai::query()')) {
    failAdvancedImageTaskOwnershipContract('__invoke() does not use an explicit scoped task query');
}

if (! str_contains($statusMethod, "where('user_id', auth()->id())")) {
    failAdvancedImageTaskOwnershipContract('task lookup is not constrained to the authenticated owner');
}

if (! str_contains($statusMethod, '->findOrFail($id)')) {
    failAdvancedImageTaskOwnershipContract('missing or inaccessible task IDs do not fail closed');
}

$ownershipPosition = strpos($statusMethod, "where('user_id', auth()->id())");
$statusCheckPosition = strpos($statusMethod, '$this->shouldCheckStatus($task)');

if ($ownershipPosition === false || ($statusCheckPosition !== false && $ownershipPosition > $statusCheckPosition)) {
    failAdvancedImageTaskOwnershipContract('ownership is not established before provider status dispatch');
}

foreach (
    [
        '$this->freepikService->checkStatus($task)',
        '$this->novitaService->checkStatus($task)',
        '$this->falaiService->checkStatus($task)',
        '$this->nanoBananaService->checkStatus($task)',
    ] as $providerCall
) {
    $providerPosition = strpos($statusMethod, $providerCall);

    if ($providerPosition !== false && $ownershipPosition > $providerPosition) {
        failAdvancedImageTaskOwnershipContract('ownership is not established before provider polling');
    }
}

echo "AdvancedImage task ownership contract passed.\n";
