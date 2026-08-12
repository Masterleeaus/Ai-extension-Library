<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$providerPath = $root . '/app/extensions/BlogPilot/System/BlogPilotServiceProvider.php';
$servicePath = $root . '/app/extensions/BlogPilot/System/Services/ImageGenerationService.php';

function failBlogPilotFalImageSecurity(string $message): never
{
    fwrite(STDERR, "BlogPilot FAL image security contract failed: {$message}\n");
    exit(1);
}

$provider = is_file($providerPath) ? file_get_contents($providerPath) : false;
$service = is_file($servicePath) ? file_get_contents($servicePath) : false;

if (! is_string($provider) || ! is_string($service)) {
    failBlogPilotFalImageSecurity('unable to read BlogPilot provider/service');
}

foreach ([
    'blogpilot/fal-webhook',
    'falWebhook',
] as $retiredFragment) {
    if (str_contains($provider, $retiredFragment)) {
        failBlogPilotFalImageSecurity("retired BlogPilot webhook remains registered: {$retiredFragment}");
    }
}

foreach ([
    'https://fal.run/fal-ai/flux-pro' => 'direct FAL generation endpoint changed unexpectedly',
    'RemoteImageFetcher' => 'generated images do not use the shared safe remote-image fetcher',
    "'public'" => 'public storage contract is missing',
    "'blogpilot'" => 'BlogPilot image directory contract is missing',
    "return '/uploads/' . \$relativePath;" => 'generated thumbnail upload URL contract is missing',
] as $fragment => $failure) {
    if (! str_contains($service, $fragment)) {
        failBlogPilotFalImageSecurity($failure);
    }
}

foreach ([
    "'webhook_url'" => 'direct fal.run request still includes an ignored webhook URL',
    'file_get_contents($url)' => 'provider image URL is still fetched with raw file_get_contents',
    "uniqid('fal_', true)" => 'service still invents fallback FAL request IDs',
    "Log::info('Fal.ai API Response:" => 'service still logs the full FAL API response body',
    "Log::info('result:'" => 'service still logs the full FAL result payload',
    "Log::error('Fal.ai API Error: ' . \$response->body())" => 'service still logs the full FAL error response body',
] as $forbidden => $failure) {
    if (str_contains($service, $forbidden)) {
        failBlogPilotFalImageSecurity($failure);
    }
}

if (! str_contains($service, "\$imageUrl = \$data['images'][0]['url'] ?? null;")) {
    failBlogPilotFalImageSecurity('synchronous FAL image result extraction changed unexpectedly');
}

if (! str_contains($service, "\$result['image_url'] = \$this->downloadAndStoreImage(\$imageUrl);")) {
    failBlogPilotFalImageSecurity('synchronous FAL image is not persisted before returning');
}

echo "BlogPilot FAL image security contract passed.\n";
