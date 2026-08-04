<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Controllers;

use App\Extensions\AIPhotoshoot\System\Http\Requests\GenerateCustomPhotoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CustomPhotoshootController extends BaseAIPhotoshootController
{
    private array $generationData = [];

    public function index(): View
    {
        return view('ai-photoshoot::custom.index', [
            'settings' => $this->getUserSettings(),
        ]);
    }

    public function generate(GenerateCustomPhotoRequest $request): JsonResponse
    {
        $lockKey = $request->input('lock_key') ?: 'aps-custom-' . now()->timestamp . '-' . auth()->id();

        $productUrl = null;
        if ($request->hasFile('product_image')) {
            $productUrl = url($this->uploadFile($request->file('product_image')));
        }

        $userSettings = $this->getUserSettings();
        $ratio = (string) $request->input('ratio', $userSettings->ratio);
        $imageSize = $userSettings->getImageSizeForRatio($ratio);

        $this->generationData = [
            'prompt'        => $request->string('prompt')->toString(),
            'product_url'   => $productUrl,
            'ratio'         => $ratio,
            'num_images'    => (int) $request->input('num_images', $userSettings->num_images),
            'resolution'    => $userSettings->resolution,
            'image_width'   => $imageSize['width'] ?? null,
            'image_height'  => $imageSize['height'] ?? null,
        ];

        return $this->processGeneration($lockKey, $this->generationData);
    }

    protected function getGenerationTitle(): string
    {
        return __('Custom Photoshoot Generation');
    }

    protected function getSlugSuffix(): string
    {
        return 'aps-custom';
    }

    protected function getPrompt(): string
    {
        $userPrompt = (string) ($this->generationData['prompt'] ?? '');

        $quality = ' Requirements: preserve every provided reference image exactly — same shape, proportions, colors, materials, labels, and text. Natural lighting with realistic shadows and reflections, seamless integration of the subject with the scene, high-resolution commercial photography quality, and no distortions, artificial artifacts, or unnatural elements.';

        return trim($userPrompt) . $quality;
    }

    protected function getImageUrls(): array
    {
        return array_values(array_filter([
            $this->generationData['product_url'] ?? null,
        ]));
    }

    protected function getResponseKey(): string
    {
        return 'custom';
    }

    protected function getNumImages(): int
    {
        return max(1, (int) ($this->generationData['num_images'] ?? 1));
    }

    protected function getDemoLimitFeature(): string
    {
        return 'ai_photo_studio_custom';
    }
}
