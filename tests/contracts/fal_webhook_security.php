<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$files = [
    'verifier' => $root . '/app/Services/Security/FalWebhookVerifier.php',
    'processor' => $root . '/app/Services/Ai/FalImageWebhookProcessor.php',
    'flux_provider' => $root . '/app/extensions/FluxPro/System/FluxProServiceProvider.php',
    'flux_controller' => $root . '/app/extensions/FluxPro/System/Http/Controllers/FalAIWebhookController.php',
    'nano_provider' => $root . '/app/extensions/NanoBanana/System/NanoBananaServiceProvider.php',
    'nano_controller' => $root . '/app/extensions/NanoBanana/System/Http/Controllers/FalAIWebhookController.php',
    'seedream_provider' => $root . '/app/extensions/SeeDreamV4/System/SeeDreamV4ServiceProvider.php',
    'seedream_controller' => $root . '/app/extensions/SeeDreamV4/System/Http/Controllers/FalAIWebhookController.php',
];

function failFalWebhookSecurityContract(string $message): never
{
    fwrite(STDERR, "FAL webhook security contract failed: {$message}\n");
    exit(1);
}

function readFalWebhookFile(string $label, string $path): string
{
    $contents = is_file($path) ? file_get_contents($path) : false;

    if (! is_string($contents)) {
        failFalWebhookSecurityContract("unable to read {$label}");
    }

    return $contents;
}

$source = [];
foreach ($files as $label => $path) {
    $source[$label] = readFalWebhookFile($label, $path);
}

$verifier = $source['verifier'];
$processor = $source['processor'];

foreach ([
    'X-Fal-Webhook-Request-Id',
    'X-Fal-Webhook-User-Id',
    'X-Fal-Webhook-Timestamp',
    'X-Fal-Webhook-Signature',
    'MAX_AGE_SECONDS',
    '300',
    'https://rest.fal.ai/.well-known/jwks.json',
    'Cache::remember',
    '21600',
    'getContent()',
    "hash('sha256'",
    "implode(\"\\n\"",
    'hex2bin',
    'base64_decode',
    'sodium_crypto_sign_verify_detached',
] as $fragment) {
    if (! str_contains($verifier, $fragment)) {
        failFalWebhookSecurityContract("shared verifier is missing required control: {$fragment}");
    }
}

$verifierPosition = strpos($processor, 'assertValid($request)');
$taskLookupPosition = strpos($processor, 'UserOpenai::query()');

if ($verifierPosition === false || $taskLookupPosition === false || $verifierPosition > $taskLookupPosition) {
    failFalWebhookSecurityContract('FAL signature verification must occur before UserOpenai lookup');
}

foreach ([
    "header('X-Fal-Webhook-Request-Id')" => 'processor does not bind signed request ID to body request ID',
    'hash_equals' => 'processor does not compare signed/body request IDs safely',
    "'response', 'FL'" => 'processor no longer scopes task lookup to FAL tasks',
    "'COMPLETED'" => 'processor has no idempotent completed-task branch',
    "'ERROR'" => 'processor does not handle FAL error callbacks',
    "'FAILED'" => 'processor does not mark FAL error callbacks failed',
    "'OK'" => 'processor does not require FAL success status',
    'data_get($payload, \'images\')' => 'processor does not read FAL image payload',
    'is_array($images)' => 'processor does not validate image collection type',
    'empty($images)' => 'processor does not reject empty image collections',
    'RemoteImageFetcher' => 'processor does not use the SSRF-safe remote image fetcher',
    'SettingTwo' => 'processor does not preserve configured image storage selection',
    "'r2'" => 'processor does not preserve R2 storage support',
    "'s3'" => 'processor does not preserve S3 storage support',
    "'public'" => 'processor does not preserve public storage support',
    'Cache::lock' => 'processor does not serialize duplicate webhook deliveries',
] as $fragment => $failure) {
    if (! str_contains($processor, $fragment)) {
        failFalWebhookSecurityContract($failure);
    }
}

foreach (['flux', 'nano', 'seedream'] as $provider) {
    $providerSource = $source[$provider . '_provider'];
    $controllerSource = $source[$provider . '_controller'];

    if (! str_contains($providerSource, "->post('generator/webhook/fal-ai'")) {
        failFalWebhookSecurityContract("{$provider} provider does not restrict the shared FAL webhook route to POST");
    }

    if (str_contains($providerSource, "->any('generator/webhook/fal-ai'")) {
        failFalWebhookSecurityContract("{$provider} provider still exposes the shared FAL webhook route with ANY");
    }

    if (! str_contains($controllerSource, 'FalImageWebhookProcessor')) {
        failFalWebhookSecurityContract("{$provider} controller does not delegate to the shared FAL webhook processor");
    }

    if (str_contains($controllerSource, 'FluxProQueueCheck')) {
        failFalWebhookSecurityContract("{$provider} controller still depends on the legacy FluxProQueueCheck downloader");
    }

    if (str_contains($controllerSource, 'UserOpenai::query()')) {
        failFalWebhookSecurityContract("{$provider} controller still duplicates task lookup instead of using the shared processor");
    }
}

echo "FAL webhook security contract passed.\n";
