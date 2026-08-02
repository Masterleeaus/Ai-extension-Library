<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AIPhotoshootController extends Controller
{
    public function __invoke(): View
    {
        return view('ai-photoshoot::index', [
            'templates' => config('ai-photoshoot.templates', []),
        ]);
    }
}
