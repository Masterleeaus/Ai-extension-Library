<?php

namespace App\Extensions\AiMusicPro\System\Http\Controllers;

use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use App\Services\Common\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AiMusicProSettingsController extends Controller
{
    public function index()
    {
        return view('ai-music-pro::settings.index');
    }

    public function update(Request $request): RedirectResponse
    {
        if (Helper::appIsNotDemo()) {
            $request->validate([
                'ai_music_pro_default_provider' => 'required|in:elevenlabs,lyria3clip,lyria3pro',
            ]);

            setting([
                'ai_music_pro_default_provider' => $request->ai_music_pro_default_provider,
            ])->save();

            app(MenuService::class)->regenerate();
        }

        return back()->with(['message' => __('Updated Successfully'), 'type' => 'success']);
    }
}
