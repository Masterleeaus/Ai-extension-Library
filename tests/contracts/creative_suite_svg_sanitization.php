<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$sanitizerPath = $root . '/app/Services/Security/SvgSanitizer.php';
$controllerPath = $root . '/app/extensions/CreativeSuite/System/Http/Controllers/ImageUploadController.php';

function failCreativeSuiteSvgContract(string $message): never
{
    fwrite(STDERR, "CreativeSuite SVG sanitization contract failed: {$message}\n");
    exit(1);
}

if (! is_file($sanitizerPath)) {
    failCreativeSuiteSvgContract('shared SvgSanitizer service does not exist');
}

$controller = file_get_contents($controllerPath);
if ($controller === false) {
    failCreativeSuiteSvgContract('unable to read ImageUploadController.php');
}

foreach ([
    'use App\\Services\\Security\\SvgSanitizer;' => 'controller does not import the shared sanitizer',
    'SvgSanitizer $sanitizer' => 'controller does not receive the sanitizer',
    '$sanitizer->sanitize(' => 'controller does not sanitize SVG bytes before storage',
    "Str::uuid() . '.svg'" => 'controller does not generate the stored SVG filename server-side',
    "Storage::disk('uploads')->put(" => 'controller does not store sanitized XML explicitly',
] as $needle => $message) {
    if (! str_contains($controller, $needle)) {
        failCreativeSuiteSvgContract($message);
    }
}

require_once $sanitizerPath;

$sanitizerClass = 'App\\Services\\Security\\SvgSanitizer';
$sanitizer = new $sanitizerClass();

$malicious = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 100 100" onload="alert(1)">
  <?attack value?>
  <script>alert(document.domain)</script>
  <style>@import url(https://evil.example/style.css); rect { fill: url(https://evil.example/fill); }</style>
  <foreignObject><body xmlns="http://www.w3.org/1999/xhtml"><img src="x" onerror="alert(1)" /></body></foreignObject>
  <a href="javascript:alert(1)"><rect width="10" height="10" /></a>
  <image href="https://evil.example/pixel.png" width="10" height="10" />
  <use href="//evil.example/icons.svg#shape" />
  <rect id="safe" width="20" height="20" onclick="alert(1)" style="fill:red" fill="url(https://evil.example/fill)" />
  <defs><linearGradient id="gradient"><stop offset="0" stop-color="#fff" /></linearGradient></defs>
  <rect width="100" height="100" fill="url(#gradient)" />
  <use href="#safe" />
</svg>
SVG;

$clean = $sanitizer->sanitize($malicious);

foreach ([
    '<script' => 'script element survived',
    '<style' => 'style element survived',
    '<foreignObject' => 'foreignObject survived',
    '<image' => 'external image element survived',
    '<a ' => 'link element survived',
    'onload=' => 'root event handler survived',
    'onclick=' => 'element event handler survived',
    'style=' => 'inline CSS survived',
    'evil.example' => 'external resource URL survived',
    'javascript:' => 'scriptable URL survived',
    '<?attack' => 'processing instruction survived',
] as $needle => $message) {
    if (stripos($clean, $needle) !== false) {
        failCreativeSuiteSvgContract($message);
    }
}

foreach ([
    '<linearGradient' => 'valid gradient was removed',
    'fill="url(#gradient)"' => 'fragment-local paint reference was removed',
    'href="#safe"' => 'fragment-local use reference was removed',
] as $needle => $message) {
    if (! str_contains($clean, $needle)) {
        failCreativeSuiteSvgContract($message);
    }
}

$valid = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
  <defs>
    <linearGradient id="g"><stop offset="0" stop-color="#fff"/><stop offset="1" stop-color="#000"/></linearGradient>
    <clipPath id="clip"><rect x="0" y="0" width="50" height="50"/></clipPath>
    <symbol id="dot"><circle cx="5" cy="5" r="5"/></symbol>
  </defs>
  <g clip-path="url(#clip)"><rect width="100" height="100" fill="url(#g)"/><use href="#dot" x="10" y="10"/></g>
</svg>
SVG;

$validClean = $sanitizer->sanitize($valid);
foreach (['linearGradient', 'clipPath', 'url(#clip)', 'url(#g)', 'href="#dot"'] as $needle) {
    if (! str_contains($validClean, $needle)) {
        failCreativeSuiteSvgContract("valid SVG feature was removed: {$needle}");
    }
}

foreach ([
    '<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg"><text>&xxe;</text></svg>',
    '<html><body>not svg</body></html>',
    '<svg xmlns="http://www.w3.org/2000/svg"><path></svg>',
] as $unsafeDocument) {
    try {
        $sanitizer->sanitize($unsafeDocument);
        failCreativeSuiteSvgContract('unsafe or malformed XML did not fail closed');
    } catch (RuntimeException) {
        // Expected.
    }
}

echo "CreativeSuite SVG sanitization contract passed.\n";
