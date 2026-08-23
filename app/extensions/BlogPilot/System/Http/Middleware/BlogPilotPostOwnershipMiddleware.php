<?php

declare(strict_types=1);

namespace App\Extensions\BlogPilot\System\Http\Middleware;

use App\Extensions\BlogPilot\System\Models\BlogPilotPost;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlogPilotPostOwnershipMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $routePost = $request->route('post');
        $postId = $routePost instanceof BlogPilotPost
            ? $routePost->getKey()
            : $routePost;

        $post = BlogPilotPost::query()
            ->whereKey($postId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $bodyId = $request->input('id');

        if ($bodyId !== null && ! hash_equals((string) $post->getKey(), (string) $bodyId)) {
            abort(404);
        }

        $request->route()->setParameter('post', $post);

        return $next($request);
    }
}
