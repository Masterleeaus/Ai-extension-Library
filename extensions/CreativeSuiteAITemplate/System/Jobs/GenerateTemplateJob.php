<?php

declare(strict_types=1);

namespace App\Extensions\CreativeSuiteAITemplate\System\Jobs;

use App\Domains\Engine\Enums\EngineEnum;
use App\Domains\Entity\EntityServiceProvider;
use App\Domains\Entity\Facades\Entity;
use App\Extensions\CreativeSuiteAITemplate\System\Http\Controllers\CreativeSuiteAITemplateController;
use App\Models\Usage;
use App\Services\Ai\AiCompletionService;
use App\Services\Ai\OpenAI\Image\CreateImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateTemplateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    /**
     * @param  array{width:int,height:int}  $dimensions
     */
    public function __construct(
        public string $taskId,
        public int $userId,
        public string $systemPrompt,
        public string $userMessage,
        public array $dimensions,
        public ?string $engine,
        public ?string $description,
        public bool $generateAiReference,
        public ?string $uploadedReferencePath,
    ) {}

    public function handle(): void
    {
        $cacheKey = "cs-template:{$this->taskId}";

        app(EntityServiceProvider::class, ['app' => app()])->boot();

        // Authenticate as the user who initiated the request so credit /
        // team scopes resolve correctly inside the AI services.
        Auth::loginUsingId($this->userId);

        $engine = $this->engine ? EngineEnum::fromSlug($this->engine) : null;

        $userMessage = $this->userMessage;
        $referenceImagePath = $this->uploadedReferencePath;

        if (! $referenceImagePath && $this->generateAiReference) {
            $referenceImagePath = $this->generateDesignReference($this->description ?? '', $this->dimensions);

            if ($referenceImagePath) {
                Cache::put($cacheKey, [
                    'status'          => 'processing',
                    'user_id'         => $this->userId,
                    'reference_image' => url(str_replace(public_path(), '', $referenceImagePath)),
                ], 1800);
            }
        }

        if ($referenceImagePath) {
            $userMessage .= ' A reference design image is attached. Analyze its layout, composition, color palette, typography placement, and visual hierarchy. Replicate the structure closely in your Konva JSON output.';
            $aiResponse = app(AiCompletionService::class)->completeWithImagePath(
                $this->systemPrompt,
                $userMessage,
                $referenceImagePath,
                $engine
            );
        } else {
            $aiResponse = app(AiCompletionService::class)->complete(
                $this->systemPrompt,
                $userMessage,
                $engine
            );
        }

        $controller = app(CreativeSuiteAITemplateController::class);
        $templateData = $controller->processAiTemplateResponse($aiResponse, $this->dimensions);

        $driver = Entity::driver();
        $driver->input($aiResponse)->calculateCredit()->decreaseCredit();
        Usage::getSingle()->updateWordCounts($driver->calculate());

        $imageTasks = $controller->submitPlaceholderImageGenerations($templateData);

        Cache::put($cacheKey, [
            'status'          => 'completed',
            'user_id'         => $this->userId,
            'data'            => $templateData,
            'image_tasks'     => $imageTasks,
            'reference_image' => $referenceImagePath ? url(str_replace(public_path(), '', $referenceImagePath)) : null,
            'description'     => $this->description,
        ], 1800);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Creative Suite template generation failed', [
            'task_id' => $this->taskId,
            'error'   => $exception?->getMessage(),
        ]);

        Cache::put("cs-template:{$this->taskId}", [
            'status'  => 'failed',
            'user_id' => $this->userId,
            'error'   => $exception?->getMessage() ?? __('Template generation failed.'),
        ], 1800);
    }

    /**
     * @param  array{width:int,height:int}  $dimensions
     */
    private function generateDesignReference(string $description, array $dimensions): ?string
    {
        if ($description === '') {
            return null;
        }

        $ratio = $dimensions['width'] / $dimensions['height'];

        $size = match (true) {
            $ratio > 1.3  => '1536x1024',
            $ratio < 0.77 => '1024x1536',
            default       => '1024x1024',
        };

        $orientation = match (true) {
            $ratio > 1.3  => 'wide landscape',
            $ratio < 0.77 => 'tall portrait',
            default       => 'square',
        };

        $prompt = "A polished, complete {$orientation} social media graphic for: {$description}. "
            . 'IMPORTANT: This must be a FINISHED design with REAL photographs and REAL text — absolutely NO placeholder images, '
            . 'NO "your photo here" areas, NO grey boxes, NO camera icons, NO empty frames. Every area must have actual content. '
            . 'Use a color palette that matches the theme (e.g. earthy tones for coffee, cool blues for tech, warm pastels for beauty etc.). Choose colors that feel natural for the subject matter. '
            . 'Clean layout with 3-4 sections max. Include at most 1-2 photographs — keep it simple. '
            . 'Bold heading text, a subheading, and a call-to-action. '
            . 'Use bordered frames, outlined shapes, and stroke effects around elements for visual structure. '
            . 'Avoid complex shapes, ribbons, inner corner radius and shadows. '
            . 'Simple and easy to recreate with basic shapes and text blocks.';

        try {
            $model = setting('creative_suite_template_image_model', 'gpt-image-1');

            $result = app(CreateImageService::class)
                ->setModel($model)
                ->setPrompt($prompt)
                ->setSize($size)
                ->generate();

            if ($result['status'] ?? false) {
                return public_path($result['path']);
            }
        } catch (Throwable $e) {
            Log::info('Design reference generation failed, proceeding without reference', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
