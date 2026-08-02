<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Controllers;

use App\Domains\Entity\Enums\EntityEnum;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AIPhotoshootSettingController extends Controller
{
    private function getVideoModels(): array
    {
        return [
            EntityEnum::VEO_3_1_IMAGE_TO_VIDEO->value      => 'Veo 3.1 Image to Video',
            EntityEnum::VEO_3_1_IMAGE_TO_VIDEO_FAST->value => 'Veo 3.1 Image to Video (Fast)',
        ];
    }

    public function index(Request $request): RedirectResponse|View
    {
        $openAIModelValues = array_values(array_keys(array_filter(
            AIPhotoshootImageModelRegistry::models(),
            static fn ($meta) => ($meta['provider'] ?? null) === AIPhotoshootImageModelRegistry::PROVIDER_OPENAI,
        )));

        $imageModels = AIPhotoshootImageModelRegistry::getModelsForAdminSelect();
        $imageModelEnabled = [];
        foreach (array_keys($imageModels) as $value) {
            $imageModelEnabled[$value] = AIPhotoshootImageModelRegistry::isModelEnabled($value);
        }

        $falModelValues = array_values(array_keys(array_filter(
            AIPhotoshootImageModelRegistry::models(),
            static fn ($meta) => ($meta['provider'] ?? null) === AIPhotoshootImageModelRegistry::PROVIDER_FAL,
        )));

        return view('ai-photoshoot::admin.settings', [
            'videoModels'            => $this->getVideoModels(),
            'currentVideoModel'      => setting('ai-photoshoot-video-default-model', EntityEnum::VEO_3_1_IMAGE_TO_VIDEO->value),
            'imageModels'            => $imageModels,
            'imageModelEnabled'      => $imageModelEnabled,
            'currentImageModel'      => AIPhotoshootImageModelRegistry::getDefaultModel()->value,
            'openAIModelValues'      => $openAIModelValues,
            'falModelValues'         => $falModelValues,
            'qualityOptions'         => AIPhotoshootImageModelRegistry::getOpenAIQualityOptions(),
            'currentQuality'         => AIPhotoshootImageModelRegistry::getOpenAIQuality(),
            'sizeOptions'            => AIPhotoshootImageModelRegistry::getOpenAISizeOptions(),
            'currentSize'            => AIPhotoshootImageModelRegistry::getOpenAISize(),
            'falRatioOptions'        => AIPhotoshootImageModelRegistry::getFalRatioOptions(),
            'currentFalRatio'        => AIPhotoshootImageModelRegistry::getFalRatio(),
            'falResolutionOptions'   => AIPhotoshootImageModelRegistry::getFalResolutionOptions(),
            'currentFalResolution'   => AIPhotoshootImageModelRegistry::getFalResolution(),
            'currentMaxUploadSizeMb' => (int) setting('ai-photoshoot-max-upload-size-mb', 5),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (Helper::appIsDemo()) {
            return to_route('dashboard.user.index')->with([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $imageModelKeys = array_values(array_filter(
            array_keys(AIPhotoshootImageModelRegistry::getModelsForAdminSelect()),
            static fn (string $value) => AIPhotoshootImageModelRegistry::isModelEnabled($value),
        ));

        $data = $request->validate([
            'ai-photoshoot-video-default-model' => 'required|string',
            'ai-photoshoot-image-default-model' => 'required|string|in:' . implode(',', $imageModelKeys),
            'ai-photoshoot-openai-quality'      => 'required|string|in:' . implode(',', AIPhotoshootImageModelRegistry::OPENAI_QUALITY_VALUES),
            'ai-photoshoot-openai-size'         => 'required|string|in:' . implode(',', AIPhotoshootImageModelRegistry::OPENAI_SIZE_VALUES),
            'ai-photoshoot-fal-ratio'           => 'required|string|in:' . implode(',', AIPhotoshootImageModelRegistry::FAL_RATIO_VALUES),
            'ai-photoshoot-fal-resolution'      => 'required|string|in:' . implode(',', AIPhotoshootImageModelRegistry::FAL_RESOLUTION_VALUES),
            'ai-photoshoot-max-upload-size-mb'  => 'required|integer|min:1|max:100',
        ]);

        setting($data)->save();

        return back()->with([
            'type'    => 'success',
            'message' => trans('Settings updated successfully.'),
        ]);
    }
}
