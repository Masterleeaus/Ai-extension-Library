<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$secureServicePath = $root . '/app/extensions/BlogPilot/System/Services/SecurePostGenerationService.php';
$providerPath = $root . '/app/extensions/BlogPilot/System/BlogPilotServiceProvider.php';
$commandPath = $root . '/app/extensions/BlogPilot/System/Console/Commands/GenerateAgentPostsCommand.php';

function failBlogPilotGeneratedPostSchema(string $message): never
{
    fwrite(STDERR, "BlogPilot generated-post schema contract failed: {$message}\n");
    exit(1);
}

$service = is_file($secureServicePath) ? file_get_contents($secureServicePath) : false;
$provider = is_file($providerPath) ? file_get_contents($providerPath) : false;
$command = is_file($commandPath) ? file_get_contents($commandPath) : false;

if (! is_string($service) || ! is_string($provider) || ! is_string($command)) {
    failBlogPilotGeneratedPostSchema('unable to read BlogPilot secure post generation runtime');
}

foreach ([
    'JsonException' => 'exception-based JSON parsing is not used',
    'JSON_THROW_ON_ERROR' => 'JSON decoding does not fail closed',
    'JSON_MAX_DEPTH' => 'JSON nesting depth is not bounded',
    'validateGeneratedPost' => 'generated post schema validator is missing',
    'MAX_TITLE_LENGTH' => 'title size is not bounded',
    'MAX_CONTENT_LENGTH' => 'content size is not bounded',
    'MAX_TERM_LENGTH' => 'tag/category size is not bounded',
    "'post_title'" => 'post_title is not part of the validated schema',
    "'post_content'" => 'post_content is not part of the validated schema',
    "'post_tags'" => 'post_tags is not part of the validated schema',
    "'post_categories'" => 'post_categories is not part of the validated schema',
    'count($tags) !== 3' => 'exactly three tags are not required',
    'count($categories) !== 1' => 'exactly one category is not required',
    "'success' => true" => 'validated result does not explicitly mark success',
    "setSafeMode(true)" => 'markdown safe mode is not enabled when supported',
] as $fragment => $failure) {
    if (! str_contains($service, $fragment)) {
        failBlogPilotGeneratedPostSchema($failure);
    }
}

foreach ([
    "Log::error('PostGenerationService API Error: ' . \$response->body())" => 'full API response body is logged by the secure generator',
    "Log::warning('Failed to parse post content as JSON:" => 'raw model content is logged by the secure generator',
    "'content' => \$content" => 'raw model content is included in structured logs',
] as $forbidden => $failure) {
    if (str_contains($service, $forbidden)) {
        failBlogPilotGeneratedPostSchema($failure);
    }
}

if (! str_contains($provider, '$this->app->bind(PostGenerationService::class, SecurePostGenerationService::class);')) {
    failBlogPilotGeneratedPostSchema('BlogPilot does not bind the secure generator as the canonical PostGenerationService runtime');
}

foreach ([
    'protected PostGenerationService $postService;' => 'scheduler no longer depends on the container-resolved PostGenerationService contract',
    'if ($post[\'success\']) {' => 'scheduler no longer gates persistence on a successful generated-post result',
    'BlogPilotPost::create([' => 'scheduler persistence contract changed unexpectedly',
] as $fragment => $failure) {
    if (! str_contains($command, $fragment)) {
        failBlogPilotGeneratedPostSchema($failure);
    }
}

$successGatePosition = strpos($command, "if (\$post['success']) {");
$createPosition = strpos($command, 'BlogPilotPost::create([');

if ($successGatePosition === false || $createPosition === false || $successGatePosition > $createPosition) {
    failBlogPilotGeneratedPostSchema('scheduler can persist a post before the secure success gate');
}

echo "BlogPilot generated-post schema contract passed.\n";
