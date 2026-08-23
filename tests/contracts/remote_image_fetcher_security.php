<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$fetcherPath = $root . '/app/Services/Security/RemoteImageFetcher.php';
$creativeSuitePath = $root . '/app/extensions/CreativeSuite/System/Http/Controllers/CreativeSuiteAIController.php';
$freepikPath = $root . '/app/extensions/AdvancedImage/System/Services/AdvancedFreepikService.php';
$downloadTraitPath = $root . '/app/extensions/AdvancedImage/System/Services/Traits/HasDownloadImage.php';

function failRemoteImageFetcherSecurityContract(string $message): never
{
    fwrite(STDERR, "Remote image fetcher security contract failed: {$message}\n");
    exit(1);
}

$fetcher = is_file($fetcherPath) ? file_get_contents($fetcherPath) : false;
$creativeSuite = file_get_contents($creativeSuitePath);
$freepik = file_get_contents($freepikPath);
$downloadTrait = file_get_contents($downloadTraitPath);

foreach ([
    'RemoteImageFetcher.php' => $fetcher,
    'CreativeSuiteAIController.php' => $creativeSuite,
    'AdvancedFreepikService.php' => $freepik,
    'HasDownloadImage.php' => $downloadTrait,
] as $file => $contents) {
    if ($contents === false) {
        failRemoteImageFetcherSecurityContract("unable to read {$file}");
    }
}

foreach ([
    '($parts[\'scheme\'] ?? null) !== \'https\'',
    'isset($parts[\'user\'])',
    'isset($parts[\'pass\'])',
    '($parts[\'port\'] ?? 443) !== 443',
    'dns_get_record',
    'FILTER_FLAG_NO_PRIV_RANGE',
    'FILTER_FLAG_NO_RES_RANGE',
    'CURLOPT_RESOLVE',
    "'allow_redirects' => false",
    "'progress'",
    'MAX_BYTES',
    'FILEINFO_MIME_TYPE',
    "'image/png'",
    "'image/jpeg'",
    "'image/webp'",
] as $requiredFragment) {
    if (! str_contains($fetcher, $requiredFragment)) {
        failRemoteImageFetcherSecurityContract("shared fetcher is missing required control: {$requiredFragment}");
    }
}

foreach ([
    '127.0.0.0/8',
    '169.254.0.0/16',
    '10.0.0.0/8',
    '172.16.0.0/12',
    '192.168.0.0/16',
    '100.64.0.0/10',
    '192.0.2.0/24',
    '198.51.100.0/24',
    '203.0.113.0/24',
    '::1/128',
    'fc00::/7',
    'fe80::/10',
    '2001:db8::/32',
] as $blockedRange) {
    if (! str_contains($fetcher, $blockedRange)) {
        failRemoteImageFetcherSecurityContract("shared fetcher does not explicitly block {$blockedRange}");
    }
}

foreach ([
    'CreativeSuiteAIController.php' => $creativeSuite,
    'AdvancedFreepikService.php' => $freepik,
    'HasDownloadImage.php' => $downloadTrait,
] as $file => $contents) {
    if (! str_contains($contents, 'RemoteImageFetcher')) {
        failRemoteImageFetcherSecurityContract("{$file} does not use the shared safe fetcher");
    }

    if (str_contains($contents, 'Http::get($url)')) {
        failRemoteImageFetcherSecurityContract("{$file} still performs an unrestricted remote GET");
    }
}

require_once $fetcherPath;

$policy = new App\Services\Security\RemoteImageFetcher;

foreach ([
    'http://example.com/image.png',
    'https://user:secret@example.com/image.png',
    'https://example.com:8443/image.png',
    'https://localhost/image.png',
    'https://127.0.0.1/image.png',
    'https://10.0.0.1/image.png',
    'https://172.16.0.1/image.png',
    'https://192.168.1.1/image.png',
    'https://100.64.0.1/image.png',
    'https://169.254.169.254/latest/meta-data',
    'https://192.0.2.10/image.png',
    'https://198.51.100.10/image.png',
    'https://203.0.113.10/image.png',
    'https://[::1]/image.png',
    'https://[fc00::1]/image.png',
    'https://[fe80::1]/image.png',
    'https://[2001:db8::1]/image.png',
] as $unsafeUrl) {
    try {
        $policy->resolveTarget($unsafeUrl);
        failRemoteImageFetcherSecurityContract("unsafe target was accepted: {$unsafeUrl}");
    } catch (RuntimeException) {
        // Expected: unsafe targets fail before any outbound request.
    }
}

$publicTarget = $policy->resolveTarget('https://1.1.1.1/image.png');
if (($publicTarget['host'] ?? null) !== '1.1.1.1' || ($publicTarget['ip'] ?? null) !== '1.1.1.1') {
    failRemoteImageFetcherSecurityContract('public HTTPS IP did not pass the URL policy');
}

echo "Remote image fetcher security contract passed.\n";
