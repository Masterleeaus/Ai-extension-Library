<?php

declare(strict_types=1);

namespace App\Extensions\BlogPilot\System\Http\Controllers;

use App\Extensions\BlogPilot\System\Models\BlogPilot;
use App\Extensions\BlogPilot\System\Models\BlogPilotPost;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class BlogPilotPostsController extends Controller
{
    public function __invoke(): View
    {
        $posts = BlogPilotPost::query()
            ->where('user_id', auth()->id())
            ->orderBy('scheduled_at', 'desc')
            ->paginate(999);

        $agents = BlogPilot::query()->where('user_id', auth()->id())->get();

        return view('blogpilot::posts.index', [
            'posts'         => $posts,
            'platformEnums' => '',
            'platforms'     => '',
            'agents'        => $agents,
        ]);
    }
}
