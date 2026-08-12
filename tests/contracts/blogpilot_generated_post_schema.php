<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$servicePath = $root . '/app/extensions/BlogPilot/System/Services/PostGenerationService.php';
$commandPath = $root . '/app/extensions/BlogPilot/System/Console/Commands/GenerateAgentPostsCommand.php';

function failBlogPilotGeneratedPostSchema(string $message): never
{
    fwrite(STDERR, "BlogPilot generated-post schema contract failed: {$message}\n");
    exit(1);
}

$service = is_file($servicePath) ? file_get_contents($servicePath) : false;
$command = is_file($commandPath) ? file_get_contents($commandPath) : false;

if (! is_string($service) || ! is_string($command)) {
    failBlogPilotGeneratedPostSchema('unable to read BlogPilot post generation runtime');
}

foreach ([
    'JsonException' => 'exception-based JSON parsing is not used',
    'JSON_THROW_ON_ERROR' => 'JSON decoding does not fail closed',
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
] as $fragment => $failure) {
    if (! str_contains($service, $fragment)) {
        failBlogPilotGeneratedPostSchema($failure);
    }
}

foreach ([
    "Log::error('PostGenerationService API Error: ' . \$response->body())" => 'full API response body is still logged',
    "Log::warning('Failed to parse post content as JSON:" => 'raw model content is still logged on parse failure',
] as $forbidden => $failure) {
    if (str_contains($service, $forbidden)) {
        failBlogPilotGeneratedPostSchema($failure);
    }
}

foreach ([
    "'title'        => \$post['post_title']," => 'scheduler still does not require validated title',
    "'content'      => \$post['post_content']," => 'scheduler still does not require validated content',
    "'tags'         => \$post['post_tags']," => 'scheduler still does not require validated tags',
    "'categories'   => \$post['post_categories']," => 'scheduler still does not require validated categories',
] as $fragment => $failure) {
    if (! str_contains($command, $fragment)) {
        failBlogPilotGeneratedPostSchema($failure);
    }
}

foreach ([
    "\$post['post_title'] ?? 'Untitled Post'",
    "\$post['post_content'] ?? ''",
    "\$post['post_tags'] ?? []",
    "\$post['post_categories'] ?? []",
] as $fallback) {
    if (str_contains($command, $fallback)) {
        failBlogPilotGeneratedPostSchema("scheduler still masks malformed model output with fallback: {$fallback}");
    }
}

echo "BlogPilot generated-post schema contract passed.\n";
