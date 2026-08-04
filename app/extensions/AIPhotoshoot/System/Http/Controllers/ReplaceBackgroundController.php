<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Controllers;

use App\Extensions\AIPhotoshoot\System\Http\Requests\GenerateReplaceBgPhotoRequest;
use App\Extensions\AIPhotoshoot\System\Models\AIPhotoshootBackground;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReplaceBackgroundController extends BaseAIPhotoshootController
{
    private array $generationData = [];

    public function index(): View
    {
        return view('ai-photoshoot::replace-background.index', [
            'backgrounds' => config('ai-photoshoot.backgrounds', []),
            'settings'    => $this->getUserSettings(),
        ]);
    }

    public function generate(GenerateReplaceBgPhotoRequest $request): JsonResponse
    {
        $lockKey = $request->input('lock_key') ?: 'aps-replace-bg-' . now()->timestamp . '-' . auth()->id();

        $productUrl = url($this->uploadFile($request->file('product_image')));
        $background = $this->resolveBackground($request);

        $userSettings = $this->getUserSettings();
        $ratio = (string) $request->input('ratio', $userSettings->ratio);
        $imageSize = $userSettings->getImageSizeForRatio($ratio);

        $this->generationData = [
            'product_url'     => $productUrl,
            'background_url'  => $background['url'],
            'background_name' => $background['name'],
            'ratio'           => $ratio,
            'num_images'      => (int) $request->input('num_images', $userSettings->num_images),
            'resolution'      => $userSettings->resolution,
            'image_width'     => $imageSize['width'] ?? null,
            'image_height'    => $imageSize['height'] ?? null,
        ];

        return $this->processGeneration($lockKey, $this->generationData);
    }

    /**
     * Resolve the background source. Returns both the absolute URL (for the
     * provider) and a human-readable name (for the photoshoot details modal).
     *
     * Accepts one of:
     *   - uploaded file `background_image`
     *   - preset slug from config (`background_id`)
     *   - user library id in the form `user-{id}` (`background_id`)
     *
     * @return array{url: ?string, name: ?string}
     */
    private function resolveBackground(GenerateReplaceBgPhotoRequest $request): array
    {
        if ($request->hasFile('background_image')) {
            return [
                'url'  => url($this->uploadFile($request->file('background_image'))),
                'name' => __('Custom Upload'),
            ];
        }

        $backgroundId = (string) $request->input('background_id', '');

        if ($backgroundId === '') {
            return ['url' => null, 'name' => null];
        }

        if (str_starts_with($backgroundId, 'user-')) {
            $id = (int) str_replace('user-', '', $backgroundId);
            $background = AIPhotoshootBackground::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            return [
                'url'  => $background?->image_url ? url($background->image_url) : null,
                'name' => $background?->name ?: __('My Background'),
            ];
        }

        $presets = config('ai-photoshoot.backgrounds', []);
        foreach ($presets as $preset) {
            if (($preset['slug'] ?? null) === $backgroundId) {
                return [
                    'url'  => url($preset['image']),
                    'name' => $preset['label'] ?? null,
                ];
            }
        }

        return ['url' => null, 'name' => null];
    }

    protected function getGenerationTitle(): string
    {
        return __('Replace Background Generation');
    }

    protected function getSlugSuffix(): string
    {
        return 'aps-replace-bg';
    }

    protected function getPrompt(): string
    {
        $prompt = 'Use only the provided reference images: the first image is the product (preserve this exact product in the output — same shape, proportions, colors, materials, labels, and text), the second image is the new background scene (use this as the environment behind the product). ';
        $prompt .= 'Replace the background of the product with the new background scene. ';
        $prompt .= 'Match lighting direction, intensity, and color temperature between the product and the new scene. ';
        $prompt .= 'Produce realistic contact shadows, reflections, and depth-of-field consistent with the new environment. ';
        $prompt .= 'Seamlessly blend the product with the new background so the composite looks like a single natural photograph. ';
        $prompt .= 'Strict consistency with both reference images: the product must match the first image exactly, and the background must match the second image. ';
        $prompt .= 'High-resolution commercial photography quality with no distortions, artifacts, halos, or unnatural elements.';

        return $prompt;
    }

    protected function getImageUrls(): array
    {
        return array_values(array_filter([
            $this->generationData['product_url'] ?? null,
            $this->generationData['background_url'] ?? null,
        ]));
    }

    protected function getResponseKey(): string
    {
        return 'replace_bg';
    }

    protected function getNumImages(): int
    {
        return max(1, (int) ($this->generationData['num_images'] ?? 1));
    }

    protected function getDemoLimitFeature(): string
    {
        return 'ai_photo_studio_replace_bg';
    }
}
