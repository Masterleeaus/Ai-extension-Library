<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$controllerPath = $root . '/app/extensions/CreativeSuiteAnnotations/System/Http/Controllers/CreativeSuiteAnnotationsAIController.php';
$jobPath = $root . '/app/extensions/CreativeSuiteAnnotations/System/Jobs/ProcessAnnotationEditJob.php';

function failCreativeSuiteAnnotationsFinalizationContract(string $message): never
{
    fwrite(STDERR, "CreativeSuiteAnnotations finalization security contract failed: {$message}\n");
    exit(1);
}

$controller = file_get_contents($controllerPath);
$job = file_get_contents($jobPath);

foreach ([
    'CreativeSuiteAnnotationsAIController.php' => $controller,
    'ProcessAnnotationEditJob.php' => $job,
] as $file => $contents) {
    if ($contents === false) {
        failCreativeSuiteAnnotationsFinalizationContract("unable to read {$file}");
    }
}

if (! str_contains($controller, 'use App\\Services\\Security\\RemoteImageFetcher;')) {
    failCreativeSuiteAnnotationsFinalizationContract('controller does not import the shared RemoteImageFetcher');
}

if (str_contains($controller, 'Http::get($url)')) {
    failCreativeSuiteAnnotationsFinalizationContract('controller still performs an unrestricted remote GET');
}

if (! str_contains($controller, 'app(RemoteImageFetcher::class)->fetch($url)')) {
    failCreativeSuiteAnnotationsFinalizationContract('controller does not fetch async results through the shared safe fetcher');
}

if (! str_contains($controller, "$download['extension']")) {
    failCreativeSuiteAnnotationsFinalizationContract('plain async results do not preserve the verified MIME-derived extension');
}

$claimPattern = <<<'REGEX'
/UserOpenai::query\(\)\s*->where\('id', \$userOpenai->getKey\(\)\)\s*->where\('user_id', Auth::id\(\)\)\s*->whereIn\('status', self::PENDING_STATUSES\)/s
REGEX;

if (preg_match($claimPattern, $controller) !== 1) {
    failCreativeSuiteAnnotationsFinalizationContract('FINALIZING claim is not scoped to the authenticated owner');
}

foreach ([
    'dispatchAfterResponse' => <<<'REGEX'
/ProcessAnnotationEditJob::dispatchAfterResponse\(\s*\$userOpenai->getKey\(\),\s*\(int\) Auth::id\(\),/s
REGEX,
    'queued constructor' => <<<'REGEX'
/new ProcessAnnotationEditJob\(\s*\$userOpenai->getKey\(\),\s*\(int\) Auth::id\(\),/s
REGEX,
] as $path => $pattern) {
    if (preg_match($pattern, $controller) !== 1) {
        failCreativeSuiteAnnotationsFinalizationContract("{$path} does not bind the task owner into the queued job");
    }
}

if (! str_contains($job, 'public int $userId,')) {
    failCreativeSuiteAnnotationsFinalizationContract('queued job does not persist the expected owner ID');
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
