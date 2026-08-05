<?php

declare(strict_types=1);

$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'TitanZero\\Interaction\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = $root . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

use TitanZero\Interaction\AI\AIServiceInterface;
use TitanZero\Interaction\Vertical\AI\DeterministicVerticalAIProposalFallback;
use TitanZero\Interaction\Vertical\AI\VerticalAIProposalBridge;
use TitanZero\Interaction\Vertical\AI\VerticalAIProposalSanitizer;
use TitanZero\Interaction\Vertical\AI\VerticalAIProposalValidator;
use TitanZero\Interaction\Vertical\VerticalContextComposer;

final class SecretBearingFailureAIService implements AIServiceInterface
{
    public function generate(string $prompt, array $options = []): string
    {
        throw new RuntimeException('Provider rejected API key sk-super-secret at https://upstream.example/error?id=42');
    }
}

$snapshot = (new VerticalContextComposer())->compose([], ['company_id' => 42]);
$proposal = (new VerticalAIProposalBridge(
    ai: new SecretBearingFailureAIService(),
    validator: new VerticalAIProposalValidator(),
    sanitizer: new VerticalAIProposalSanitizer(),
    fallback: new DeterministicVerticalAIProposalFallback(),
    providerId: 'openai-compatible',
    modelId: 'test-model',
    maxAttempts: 1,
))->propose($snapshot)->toArray();

$error = $proposal['audit']['validation_errors'][0] ?? '';
if (str_contains($error, 'sk-super-secret') || str_contains($error, 'upstream.example')) {
    fwrite(STDERR, "FAIL provider exception details leaked into proposal audit\n");
    exit(1);
}
if (!str_contains($error, 'RuntimeException')) {
    fwrite(STDERR, "FAIL provider audit marker does not retain the exception class\n");
    exit(1);
}

echo "PASS provider exception details are redacted from proposal audit\n";
