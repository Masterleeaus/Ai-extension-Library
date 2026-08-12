<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$providerPath = $root . '/app/extensions/BlogPilot/System/BlogPilotServiceProvider.php';
$middlewarePath = $root . '/app/extensions/BlogPilot/System/Http/Middleware/BlogPilotPostOwnershipMiddleware.php';
$postsControllerPath = $root . '/app/extensions/BlogPilot/System/Http/Controllers/BlogPilotPostsController.php';

function failBlogPilotPostOwnership(string $message): never
{
    fwrite(STDERR, "BlogPilot post ownership contract failed: {$message}\n");
    exit(1);
}

function readBlogPilotOwnershipFile(string $label, string $path): string
{
    $contents = is_file($path) ? file_get_contents($path) : false;

    if (! is_string($contents)) {
        failBlogPilotPostOwnership("unable to read {$label}");
    }

    return $contents;
}

$provider = readBlogPilotOwnershipFile('BlogPilotServiceProvider.php', $providerPath);
$middleware = readBlogPilotOwnershipFile('BlogPilotPostOwnershipMiddleware.php', $middlewarePath);
$postsController = readBlogPilotOwnershipFile('BlogPilotPostsController.php', $postsControllerPath);

foreach ([
    "->where('user_id', auth()->id())" => 'middleware does not scope the route post to the authenticated owner',
    "route('post')" => 'middleware does not inspect the route post parameter',
    'setParameter' => 'middleware does not replace the route parameter with the owned model',
    "input('id')" => 'middleware does not inspect legacy body IDs',
    'hash_equals' => 'middleware does not bind body ID to route ID safely',
    'abort(404)' => 'middleware does not fail closed on a body/route post mismatch',
] as $fragment => $failure) {
    if (! str_contains($middleware, $fragment)) {
        failBlogPilotPostOwnership($failure);
    }
}

foreach ([
    "BlogPilotPost::query()" => 'Posts page no longer loads BlogPilot posts',
    "->where('user_id', auth()->id())" => 'Posts page is not scoped to the authenticated owner',
    "->orderBy('scheduled_at', 'desc')" => 'Posts page ordering changed unexpectedly',
    '->paginate(999)' => 'Posts page pagination changed unexpectedly',
    "BlogPilot::query()->where('user_id', auth()->id())->get()" => 'Posts page agents are not owner-scoped',
    "view('blogpilot::posts.index'" => 'Posts page view contract changed unexpectedly',
] as $fragment => $failure) {
    if (! str_contains($postsController, $fragment)) {
        failBlogPilotPostOwnership($failure);
    }
}

if (! str_contains($provider, 'BlogPilotPostsController::class')) {
    failBlogPilotPostOwnership('Posts route does not use the owner-scoped posts controller');
}

if (! str_contains($provider, 'BlogPilotPostOwnershipMiddleware::class')) {
    failBlogPilotPostOwnership('post-management routes do not use the ownership middleware');
}

foreach ([
    "Route::get('posts/{post}/edit'",
    "Route::delete('posts/{post}/reject'",
    "Route::post('posts/{post}/duplicate'",
    "Route::post('posts/{post}/update'",
    "Route::post('posts/{post}/publish'",
] as $routeFragment) {
    $position = strpos($provider, $routeFragment);

    if ($position === false) {
        failBlogPilotPostOwnership("missing existing post route: {$routeFragment}");
    }

    $excerpt = substr($provider, $position, 320);

    if (! str_contains($excerpt, 'BlogPilotPostOwnershipMiddleware::class')) {
        failBlogPilotPostOwnership("post route is missing ownership middleware: {$routeFragment}");
    }
}

echo "BlogPilot post ownership contract passed.\n";
