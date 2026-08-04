<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Controllers;

use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditImageController extends BaseAIPhotoshootController
{
    private array $uploadedPaths = [];

    private string $userPrompt = '';

    public function index(): View
    {
        return view('ai-photoshoot::edit-image');
    }

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'image'  => 'required|image|max:' . AIPhotoshootImageModelRegistry::getMaxUploadSizeKb(),
            'prompt' => 'required|string|max:1000',
        ]);

        $lockKey = $request->input('lock_key') ?: 'aps-edit-' . now()->timestamp . '-' . auth()->id();

        $imagePath = $this->uploadFile($request->file('image'));

        $this->uploadedPaths = ['image' => url($imagePath)];
        $this->userPrompt = (string) $request->input('prompt');

        return $this->processGeneration($lockKey, [
            'image_path' => url($imagePath),
            'prompt'     => $this->userPrompt,
        ]);
    }

    protected function getGenerationTitle(): string
    {
        return __('AI Photoshoot Edit');
    }

    protected function getSlugSuffix(): string
    {
        return 'aps-edit';
    }

    protected function getPrompt(): string
    {
        return $this->userPrompt;
    }

    protected function getImageUrls(): array
    {
        return [$this->uploadedPaths['image']];
    }

    protected function getResponseKey(): string
    {
        return 'edit_image';
    }

    protected function getDemoLimitFeature(): string
    {
        return 'ai_photo_studio_edit';
    }
}
