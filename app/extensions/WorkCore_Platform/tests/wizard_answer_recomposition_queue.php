<?php

declare(strict_types=1);

$repoRoot = getenv('AI_EXTENSIONS_REPO_ROOT') ?: dirname(__DIR__, 3);
$vendorAutoload = getenv('WORKCORE_TEST_VENDOR_AUTOLOAD') ?: $repoRoot . '/vendor/autoload.php';
if (!is_file($vendorAutoload)) {
    fwrite(STDERR, "Missing test vendor autoload: {$vendorAutoload}\n");
    exit(2);
}
require $vendorAutoload;

spl_autoload_register(static function (string $class) use ($repoRoot): void {
    $prefix = 'App\\Domains\\WorkCore\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = $repoRoot
        . '/app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/'
        . str_replace('\\', '/', substr($class, strlen($prefix)))
        . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

use App\Domains\WorkCore\System\Modules\Wizards\Events\WizardAnswerChanged;
use App\Domains\WorkCore\System\Modules\Wizards\Jobs\EnrichWizardAnswerProposal;
use Illuminate\Contracts\Queue\ShouldQueue;

$event = new WizardAnswerChanged(
    eventPublicId: 'event-1',
    companyId: 10,
    actorId: 7,
    runPublicId: 'run-10',
    answerPublicId: 'answer-1',
    questionKey: 'company.name',
    answerRevision: 4,
    affectedSections: ['brand', 'business_profile'],
    source: 'user_entered',
    confidence: 1.0,
    risk: 'medium',
    confirmed: true,
    idempotencyKey: 'request-1',
);
$first = EnrichWizardAnswerProposal::fromEvent($event);
$second = EnrichWizardAnswerProposal::fromEvent($event);

$checks = [
    $first instanceof ShouldQueue => 'Job does not implement ShouldQueue.',
    $first->afterCommit === true => 'Job is not constrained to after-commit dispatch.',
    $first->tries === 3 => 'Job retry policy is missing.',
    $first->uniqueId() === $second->uniqueId() => 'Job identity is not deterministic.',
    !array_key_exists('value', $first->event) => 'Raw answer value leaked into the job payload.',
    $first->event['company_id'] === 10 => 'Server-derived tenant is missing.',
    $first->event['answer_revision'] === 4 => 'Answer revision is missing.',
];
foreach ($checks as $passed => $message) {
    if (!$passed) {
        fwrite(STDERR, "FAIL {$message}\n");
        exit(1);
    }
}

echo "PASS enrichment job is after-commit, deterministic and value-free\n";
