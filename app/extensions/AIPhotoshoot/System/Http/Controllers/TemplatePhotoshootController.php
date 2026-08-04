<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Controllers;

use App\Domains\Entity\Facades\Entity;
use App\Extensions\AIPhotoshoot\System\Http\Requests\GenerateTemplatePhotoRequest;
use App\Helpers\Classes\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TemplatePhotoshootController extends BaseAIPhotoshootController
{
    private array $generationData = [];

    public function index(): View
    {
        return view('ai-photoshoot::templates.index', [
            'templates' => config('ai-photoshoot.templates', []),
            'settings'  => $this->getUserSettings(),
        ]);
    }

    public function generate(GenerateTemplatePhotoRequest $request): JsonResponse
    {
        /** @var array<int, string> $templateSlugs */
        $templateSlugs = array_values(array_unique((array) $request->input('templates', [])));
        $count = count($templateSlugs);

        // Run the demo-mode rate limit check ONCE for the whole submission.
        $demoLimitResponse = Helper::checkFashionStudioDemoLimit(
            $this->getDemoLimitFeature(),
            $this->getDemoMaxAttempts()
        );
        if ($demoLimitResponse !== null) {
            return $demoLimitResponse;
        }

        // Up-front credit balance check for the entire batch so we don't
        // half-burn a user's credits when they ask for N templates.
        $totalDriver = Entity::driver($this->editModel())
            ->inputImageCount($count)
            ->calculateCredit();

        if (! $totalDriver->hasCreditBalanceForInput()) {
            return response()->json([
                'success' => false,
                'message' => __('You do not have enough credits. You need :credits credits to generate :count image(s).', [
                    'credits' => $totalDriver->getCalculatedInputCredit(),
                    'count'   => $count,
                ]),
            ], 402);
        }

        $productUrl = url($this->uploadFile($request->file('product_image')));

        $userSettings = $this->getUserSettings();
        $ratio = (string) $request->input('ratio', $userSettings->ratio);
        $imageSize = $userSettings->getImageSizeForRatio($ratio);

        $ids = [];
        $timestamp = now()->timestamp;
        $userId = (int) auth()->id();

        foreach ($templateSlugs as $slug) {
            $template = $this->resolveTemplate((string) $slug);

            $this->generationData = [
                'template_slug'     => $template['slug'],
                'template_label'    => $template['label'],
                'template_category' => $template['category'],
                'template_prompt'   => $template['prompt'],
                'product_url'       => $productUrl,
                'ratio'             => $ratio,
                'num_images'        => 1,
                'resolution'        => $userSettings->resolution,
                'image_width'       => $imageSize['width'] ?? null,
                'image_height'      => $imageSize['height'] ?? null,
            ];

            $lockKey = "aps-template-{$timestamp}-{$userId}-{$slug}";

            // Each call deducts 1 credit and creates 1 record. Demo check
            // is skipped because we already ran it once above.
            $response = $this->processGeneration($lockKey, $this->generationData, true);
            $body = $response->getData(true);

            if (! ($body['success'] ?? false)) {
                // Bubble the first failure up; any successful records that
                // already started will continue processing on the queue.
                return $response;
            }

            $ids[] = $body['id'] ?? null;
        }

        return response()->json([
            'ids'     => array_values(array_filter($ids)),
            'success' => true,
            'message' => __('Template Photoshoot started'),
        ]);
    }

    /**
     * @return array{slug: string, label: string, category: string, prompt: string, image: string}
     */
    private function resolveTemplate(string $slug): array
    {
        $templates = config('ai-photoshoot.templates', []);

        foreach ($templates as $template) {
            if (($template['slug'] ?? null) === $slug) {
                return $template;
            }
        }

        abort(422, __('Invalid template.'));
    }

    protected function getGenerationTitle(): string
    {
        $label = $this->generationData['template_label'] ?? '';

        return trim(__('Template Photoshoot') . ($label ? ' — ' . $label : ''));
    }

    protected function getSlugSuffix(): string
    {
        return 'aps-template-' . ($this->generationData['template_slug'] ?? 'generic');
    }

    protected function getPrompt(): string
    {
        $templatePrompt = trim((string) ($this->generationData['template_prompt'] ?? ''));

        $prefix = 'Keep the product from the reference image exactly as shown — identical shape, proportions, colors, materials, finish, labels, logos, and text. Build the following scene around it: ';

        return trim($prefix . $templatePrompt);
    }

    protected function getImageUrls(): array
    {
        return array_values(array_filter([
            $this->generationData['product_url'] ?? null,
        ]));
    }

    protected function getResponseKey(): string
    {
        return 'template';
    }

    protected function getNumImages(): int
    {
        return 1;
    }

    protected function getDemoLimitFeature(): string
    {
        return 'ai_photo_studio_template';
    }
}
