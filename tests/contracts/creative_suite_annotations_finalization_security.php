<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$controllerPath = $root . '/app/extensions/CreativeSuiteAnnotations/System/Http/Controllers/CreativeSuiteAnnotationsSecureAIController.php';
$providerPath = $root . '/app/extensions/CreativeSuiteAnnotations/System/CreativeSuiteAnnotationsServiceProvider.php';
$jobPath = $root . '/app/extensions/CreativeSuiteAnnotations/System/Jobs/ProcessAnnotationEditJob.php';

function failCreativeSuiteAnnotationsFinalizationContract(string $message): never
{
    fwrite(STDERR, "CreativeSuiteAnnotations finalization security contract failed: {$message}\n");
    exit(1);
}

$controller = file_get_contents($controllerPath);
$provider = file_get_contents($providerPath);
$job = file_get_contents($jobPath);

foreach ([
    'CreativeSuiteAnnotationsSecureAIController.php' => $controller,
    'CreativeSuiteAnnotationsServiceProvider.php' => $provider,
    'ProcessAnnotationEditJob.php' => $job,
] as $file => $contents) {
    if ($contents === false) {
        failCreativeSuiteAnnotationsFinalizationContract("unable to read {$file}");
    }
}

if (! str_contains($provider, 'use App\\Extensions\\CreativeSuiteAnnotations\\System\\Http\\Controllers\\CreativeSuiteAnnotationsSecureAIController;')) {
    failCreativeSuiteAnnotationsFinalizationContract('service provider does not import the secure controller');
}

foreach (['edit', 'status', 'analyze'] as $action) {
    if (! str_contains($provider, "[CreativeSuiteAnnotationsSecureAIController::class, '{$action}']")) {
        failCreativeSuiteAnnotationsFinalizationContract("{$action} route does not use the secure compatibility controller");
    }
}

if (! str_contains($controller, 'extends CreativeSuiteAnnotationsAIController')) {
    failCreativeSuiteAnnotationsFinalizationContract('secure controller does not preserve donor edit/analyse compatibility');
}

if (! str_contains($controller, 'use App\\Services\\Security\\RemoteImageFetcher;')) {
    failCreativeSuiteAnnotationsFinalizationContract('secure controller does not import the shared RemoteImageFetcher');
}

if (str_contains($controller, 'Http::get($url)')) {
    failCreativeSuiteAnnotationsFinalizationContract('active finalisation controller performs an unrestricted remote GET');
}

if (! str_contains($controller, 'app(RemoteImageFetcher::class)->fetch($url)')) {
    failCreativeSuiteAnnotationsFinalizationContract('active finalisation controller does not use the shared safe fetcher');
}

if (! str_contains($controller, '$download[\'extension\']')) {
    failCreativeSuiteAnnotationsFinalizationContract('plain async results do not preserve the verified MIME-derived extension');
}

$claimPattern = <<<'REGEX'
/UserOpenai::query\(\)\s*->where\('id', \$userOpenai->getKey\(\)\)\s*->where\('user_id', Auth::id\(\)\)\s*->whereIn\('status', self::PENDING_STATUSES\)/s
REGEX;

if (preg_match($claimPattern, $controller) !== 1) {
    failCreativeSuiteAnnotationsFinalizationContract('FINALIZING claim is not scoped to the authenticated owner');
}

foreach ([
    'public int $userId;',
    '?int $ownerId = null,',
    '$this->userId = $ownerId ?? (int) Auth::id();',
] as $requiredJobControl) {
    if (! str_contains($job, $requiredJobControl)) {
        failCreativeSuiteAnnotationsFinalizationContract("queued job is missing owner control: {$requiredJobControl}");
    }
}

$compatibleConstructor = <<<'REGEX'
/public int \$userOpenaiId,\s*public string \$prompt,\s*public string \$modelSlug,\s*public string \$imageDiskPath,\s*public \?string \$maskDiskPath = null,\s*public int \$creditCost = 0,\s*\?int \$ownerId = null,/s
REGEX;

if (preg_match($compatibleConstructor, $job) !== 1) {
    failCreativeSuiteAnnotationsFinalizationContract('queued job changed the existing constructor argument order');
}

$ownerScopedReload = <<<'REGEX'
/UserOpenai::query\(\)\s*->where\('user_id', \$this->userId\)\s*->find\(\$this->userOpenaiId\)/s
REGEX;

if (preg_match_all($ownerScopedReload, $job) < 2) {
    failCreativeSuiteAnnotationsFinalizationContract('handle() and failed() are not both owner-scoped');
}

if (str_contains($job, 'UserOpenai::query()->find($this->userOpenaiId)')) {
    failCreativeSuiteAnnotationsFinalizationContract('queued job still reloads tasks globally by ID');
}

echo "CreativeSuiteAnnotations finalization security contract passed.\n";
