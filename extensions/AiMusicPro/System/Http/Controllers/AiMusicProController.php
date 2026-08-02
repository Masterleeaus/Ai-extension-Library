<?php

namespace App\Extensions\AiMusicPro\System\Http\Controllers;

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use App\Extensions\AiMusicPro\System\Models\AiMusicPro;
use App\Extensions\AiMusicPro\System\Services\LyriaMusicService;
use App\Helpers\Classes\ApiHelper;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use App\Packages\Elevenlabs\ElevenlabsService as PackageElevenlabsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AiMusicProController extends Controller
{
    public function __construct()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 36000);
    }

    public function index(): View
    {
        $music = AiMusicPro::where('user_id', auth()->id())->orderBy('id', 'desc')->paginate(10);
        $provider = setting('ai_music_pro_default_provider', 'elevenlabs');

        return view('ai-music-pro::index', compact('music', 'provider'));
    }

    public function generate(Request $request): JsonResponse
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 36000);

        $request->validate([
            'workbook_title'  => 'required|string|max:190',
            'ai_music_prompt' => 'required|string|max:2000',
            'duration'        => 'nullable|integer|min:10|max:300',
            'music_style'     => 'nullable|string|max:190',
            'lyrics'          => 'nullable|string|max:5000',
            'images'          => 'nullable|array|max:10',
            'images.*'        => 'image|max:25600',
            'output_format'   => 'nullable|in:mp3,wav',
        ]);

        $isDemo = Helper::appIsDemo();

        if ($isDemo) {
            $duration = (int) $request->get('duration', 30);
            $chkLmt = Helper::checkDemoSecondDailyLimit($duration);
            if ($chkLmt->getStatusCode() === 429) {
                return response()->json(['message' => $chkLmt->getData()->message, 'type' => 'error']);
            }
        }

        $provider = setting('ai_music_pro_default_provider', 'elevenlabs');
		$requestedMinutes = (float) $request->get('duration', 30) / 60;

        $entityEnum = match ($provider) {
            'lyria3clip' => EntityEnum::LYRIA_3_CLIP,
            'lyria3pro'  => EntityEnum::LYRIA_3_PRO,
            default      => EntityEnum::ELEVENLABS_AI_MUSIC,
        };


        if ($provider === 'elevenlabs') {
			$driver = Entity::driver($entityEnum)->inputMinute($requestedMinutes)->calculateCredit();
        } else {
            $estimatedSeconds = $provider === 'lyria3clip' ? 30 : 120;
            $driver = Entity::driver($entityEnum)->inputSecond($estimatedSeconds)->calculateCredit();
        }

        try {
            $driver->redirectIfNoCreditBalance();
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'type' => 'error']);
        }

        try {
            if ($provider === 'elevenlabs') {
                $result = $this->generateWithElevenlabs($request);
            } else {
                $model = $provider === 'lyria3clip' ? 'lyria-3-clip-preview' : 'lyria-3-pro-preview';
                $result = $this->generateWithLyria($request, $model);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'type' => 'error']);
        }

        if ($provider === 'elevenlabs') {
            $driver->inputMinute($result['duration'])->calculateCredit()->decreaseCredit();
        } else {
            $driver->inputSecond($result['duration_seconds'])->calculateCredit()->decreaseCredit();
        }

        $this->storeMusicRecord(
            $result['file_url'],
            $request->get('ai_music_prompt', ''),
            $request->get('workbook_title', 'Song Title'),
            $result['duration'],
            $request->get('music_style'),
            $provider,
            $result['lyrics'] ?? null
        );

        if ($isDemo) {
            $clientIp = Helper::getRequestIp();
            $cacheKey = "demo_ai_usage_seconds_{$clientIp}";
            $usedSeconds = Cache::get($cacheKey, 0);
            $newTotal = $usedSeconds + ($result['duration'] * 60);
            Cache::put($cacheKey, $newTotal, now()->endOfDay());
        }

        $music = AiMusicPro::where('user_id', auth()->id())->orderBy('id', 'desc')->paginate(10);
        $html2 = view('ai-music-pro::components.generator_sidebar_table', compact('music'))->render();

        return response()->json([
            'status'  => 'success',
            'message' => __('Music generated successfully!'),
            'html2'   => $html2,
        ]);
    }

    private function generateWithElevenlabs(Request $request): array
    {
        $param = [
            'prompt'   => $request->get('ai_music_prompt', ''),
            'model_id' => 'music_v1',
        ];

        if ($request->filled('duration')) {
            $param['music_length_ms'] = $request->get('duration') * 1000;
        }

        if ($request->filled('music_style')) {
            $param['prompt'] .= ', in the style of ' . $request->get('music_style');
        }

        $elevenlabsService = new PackageElevenlabsService(ApiHelper::setElevenlabsKey());
        $response = $elevenlabsService->AiMusicModel()->submit($param);

        $fileUrl = $response->getData(true)['file_url'] ?? null;

        if (empty($fileUrl)) {
            throw new Exception(__('Failed to generate music. Please try again.'));
        }

        $uploadsPath = public_path('uploads/' . $fileUrl);

        if ($request->filled('duration')) {
            $voiceLengthMinutes = $request->get('duration') / 60;
        } else {
            $voiceLengthMinutes = measureVoiceLength($uploadsPath);
        }

        return [
            'file_url' => $fileUrl,
            'duration' => $voiceLengthMinutes,
            'lyrics'   => null,
        ];
    }

    private function generateWithLyria(Request $request, string $model): array
    {
        $prompt = $request->get('ai_music_prompt', '');

        if ($request->filled('music_style')) {
            $prompt .= ', in the style of ' . $request->get('music_style');
        }

        if ($request->filled('lyrics')) {
            $prompt .= "\n\n" . $request->get('lyrics');
        }

        $images = $request->file('images', []);
        $outputFormat = $request->get('output_format', 'mp3');

        $service = new LyriaMusicService;

        return $service->generate($model, $prompt, $images, $outputFormat);
    }

    private function storeMusicRecord(
        string $fileUrl,
        string $prompt,
        string $title,
        float $voiceLengthMinutes,
        ?string $musicStyle,
        string $provider = 'elevenlabs',
        ?string $lyrics = null
    ): void {
        $voiceLengthSeconds = $voiceLengthMinutes * 60;

        AiMusicPro::create([
            'user_id'         => auth()->id(),
            'file_path'       => $fileUrl,
            'workbook_title'  => $title,
            'ai_music_prompt' => $prompt,
            'duration'        => $voiceLengthSeconds,
            'music_style'     => $musicStyle,
            'provider'        => $provider,
            'lyrics'          => $lyrics,
        ]);
    }

    public function delete($id): RedirectResponse
    {
        $music = AiMusicPro::where('user_id', auth()->id())->where('id', $id)->firstOrFail();

        try {
            if (file_exists(public_path('uploads/' . $music->file_path))) {
                @unlink(public_path('uploads/' . $music->file_path));
            }

            $music->delete();

            return redirect()->back()->with(['message' => __('Music deleted successfully!'), 'type' => 'success']);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => __('Failed to delete music. Please try again.'), 'type' => 'error']);
        }
    }
}