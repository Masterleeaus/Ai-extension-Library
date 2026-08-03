<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Http\Controllers;

use App\Http\Controllers\Controller;

class SystemAIChatSkillsController extends Controller
{
    public function __invoke()
    {
        return view('system-ai-chat-skills::index');
    }
}
