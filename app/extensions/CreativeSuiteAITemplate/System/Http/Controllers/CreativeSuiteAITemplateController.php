<?php

namespace App\Extensions\CreativeSuiteAITemplate\System\Http\Controllers;

use App\Domains\Engine\Services\FalAIService;
use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use App\Extensions\CreativeSuiteAITemplate\System\Http\Requests\TemplateGenerationRequest;
use App\Extensions\CreativeSuiteAITemplate\System\Jobs\GenerateTemplateJob;
use App\Helpers\Classes\Helper;
use App\Helpers\Classes\RateLimiter\RateLimiter;
use App\Http\Controllers\Controller;
use App\Models\OpenAIGenerator;
use App\Models\Usage;
use App\Models\UserOpenai;
use App\Services\Ai\OpenAI\Image\CreateImageService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreativeSuiteAITemplateController extends Controller
{
    public const ASPECT_RATIO_DIMENSIONS = [
        '1:1'  => ['width' => 1080, 'height' => 1080],
        '16:9' => ['width' => 1280, 'height' => 720],
        '9:16' => ['width' => 1080, 'height' => 1920],
        '4:5'  => ['width' => 1080, 'height' => 1350],
        '5:4'  => ['width' => 1350, 'height' => 1080],
    ];

    public function generateTemplate(TemplateGenerationRequest $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            $rateLimiter = new RateLimiter('creative_suite_template_demo_rate_limit', 1);
            $clientIp = Helper::getRequestIp();

            if (! $rateLimiter->attempt($clientIp)) {
                return $this->errorResponse(__('You have reached the daily limit of 1 request. Please try again tomorrow.'));
            }
        }

        $driver = Entity::driver();

        try {
            $driver->redirectIfNoCreditBalance();
        } catch (Exception) {
            return $this->errorResponse(__('You have no credits left. Please consider upgrading your plan.'));
        }

        $dimensions = self::ASPECT_RATIO_DIMENSIONS[$request->validated('template_aspect_ratio')] ?? self::ASPECT_RATIO_DIMENSIONS['1:1'];
        $systemPrompt = $this->getTemplateSystemPrompt();
        $userMessage = $this->buildTemplateUserMessage($request, $dimensions);
        $engineValue = setting('creative_suite_template_engine');

        $uploadedReferencePath = null;

        if ($request->hasFile('template_reference')) {
            $file = $request->file('template_reference');
            $uploadedReferencePath = $file->store('creative-suite/references', 'public');
            $uploadedReferencePath = storage_path('app/public/' . $uploadedReferencePath);
        }

        $taskId = Str::uuid()->toString();
        $userId = (int) Auth::id();

        Cache::put("cs-template:{$taskId}", [
            'status'  => 'processing',
            'user_id' => $userId,
        ], 1800);

        $job = new GenerateTemplateJob(
            taskId: $taskId,
            userId: $userId,
            systemPrompt: $systemPrompt,
            userMessage: $userMessage,
            dimensions: $dimensions,
            engine: $engineValue ?: null,
            description: $request->validated('template_description'),
            generateAiReference: $request->boolean('generate_ai_reference') && ! $request->hasFile('template_reference'),
            uploadedReferencePath: $uploadedReferencePath,
        );

        // Match the dispatch convention used by CreativeSuiteAnnotations:
        // `sync` queue runs after the response so the user isn't blocked,
        // anything else goes through the configured queue worker.
        if (config('queue.default') === 'sync') {
            GenerateTemplateJob::dispatchAfterResponse(
                $job->taskId,
                $job->userId,
                $job->systemPrompt,
                $job->userMessage,
                $job->dimensions,
                $job->engine,
                $job->description,
                $job->generateAiReference,
                $job->uploadedReferencePath,
            );
        } else {
            dispatch($job)->delay(1);
        }

        return response()->json([
            'status'  => 'success',
            'task_id' => $taskId,
        ]);
    }

    public function templateStatus(string $taskId): JsonResponse
    {
        $cacheKey = "cs-template:{$taskId}";
        $task = Cache::get($cacheKey);

        if (! $task || ($task['user_id'] ?? null) !== (int) Auth::id()) {
            return $this->errorResponse(__('Task not found or expired.'));
        }

        if ($task['status'] === 'processing') {
            return response()->json([
                'status'          => 'processing',
                'reference_image' => $task['reference_image'] ?? null,
            ]);
        }

        if ($task['status'] === 'failed') {
            Cache::forget($cacheKey);

            return $this->errorResponse($task['error'] ?? __('Template generation failed.'));
        }

        // Completed — return the template data
        Cache::forget($cacheKey);

        return response()->json([
            'status'          => 'success',
            'message'         => __('Template generated successfully'),
            'data'            => $task['data'],
            'image_tasks'     => $task['image_tasks'] ?? [],
            'reference_image' => $task['reference_image'] ?? null,
            'description'     => $task['description'] ?? null,
        ]);
    }

    /**
     * @return array{stage: array{width: int, height: int}, nodes: array<string>}
     */
    public function processAiTemplateResponse(string $raw, array $dimensions): array
    {
        $raw = trim($raw);

        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $raw, $matches)) {
            $raw = $matches[1];
        }

        $jsonStart = strpos($raw, '{');
        $jsonEnd = strrpos($raw, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception(__('Failed to generate a valid template. Please try again.'));
        }

        $raw = substr($raw, $jsonStart, $jsonEnd - $jsonStart + 1);

        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Failed to generate a valid template. Please try again.'));
        }

        if (! isset($data['nodes']) || ! is_array($data['nodes']) || empty($data['nodes'])) {
            throw new Exception(__('Failed to generate a valid template. Please try again.'));
        }

        $data['stage'] = [
            'width'  => $dimensions['width'],
            'height' => $dimensions['height'],
        ];

        $allowedClassNames = ['Rect', 'Text', 'Ellipse', 'Path', 'Image', 'Star', 'RegularPolygon', 'Arc', 'Ring', 'Wedge', 'TextPath'];
        $timestamp = (int) (microtime(true) * 1000);
        $processedNodes = [];
        $counter = 0;

        foreach ($data['nodes'] as $node) {
            if (! is_array($node) || ! isset($node['className'])) {
                continue;
            }

            if (! in_array($node['className'], $allowedClassNames, true)) {
                continue;
            }

            // Handle flat format (no attrs wrapper) — move all non-className keys into attrs
            if (! isset($node['attrs'])) {
                $className = $node['className'];
                unset($node['className']);
                $node = ['className' => $className, 'attrs' => $node];
            }

            $counter++;
            $className = $node['className'];
            $type = strtolower($className);

            $node['attrs'] = array_merge([
                'draggable'          => true,
                'strokeScaleEnabled' => false,
                'perfectDrawEnabled' => false,
                'stroke'             => '',
                'strokeWidth'        => 0,
            ], $node['attrs']);

            $node['attrs']['id'] = "{$type}-{$counter}-{$timestamp}";
            $node['attrs']['name'] = "{$className} {$counter}";
            $timestamp++;

            if ($className === 'Image') {
                $node['attrs'] = array_merge($node['attrs'], [
                    'text'              => 'Double click to edit',
                    'fontSize'          => 30,
                    'fontStyle'         => 'normal 400',
                    'fillPriority'      => 'pattern',
                    'fillCover'         => true,
                    'fillPatternRepeat' => 'no-repeat',
                ]);
            }

            // Sanitize fontStyle: Konva expects "normal|italic WEIGHT" format.
            if (isset($node['attrs']['fontStyle'])) {
                $node['attrs']['fontStyle'] = $this->sanitizeFontStyle($node['attrs']['fontStyle']);
            }

            // Remove explicit height from Text/TextPath — Konva auto-calculates it.
            // Also trim text content — AI often adds leading/trailing whitespace.
            if (in_array($className, ['Text', 'TextPath'], true)) {
                unset($node['attrs']['height']);
                if (isset($node['attrs']['text'])) {
                    $node['attrs']['text'] = trim($node['attrs']['text']);
                }
            }

            $processedNodes[] = json_encode($node);
        }

        if (empty($processedNodes)) {
            throw new Exception(__('Failed to generate a valid template. Please try again.'));
        }

        return [
            'stage' => $data['stage'],
            'nodes' => $processedNodes,
        ];
    }

    public function getTemplateSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a professional award-winning graphic designer that outputs Konva.js canvas templates as JSON.

You will receive a description of a design and stage dimensions in pixels.

CRITICAL: If a reference image is provided, you MUST closely replicate its exact layout structure:
- Match the position, size, and proportion of every visual element (images, text blocks, shapes)
- Extract the exact color palette from the reference
- Match the typography hierarchy (which text is large, which is small, positioning)
- Replicate decorative elements (circles, lines, overlays, gradients, borders)
- The output should look like a faithful recreation of the reference, not a loose interpretation

Output ONLY valid JSON matching this exact schema, with no markdown, no code fences, no explanation:

{
  "stage": { "width": <number>, "height": <number> },
  "nodes": [ <array of Konva node objects> ]
}

=== AVAILABLE NODE TYPES ===

All nodes share these base attrs:
  "x", "y", "width", "height", "fill": "<hex>", "draggable": true, "strokeScaleEnabled": false, "perfectDrawEnabled": false,
  "id": "<type>-<N>-<13digit>", "name": "<Type> <N>"
  Optional on ANY node: "stroke": "<hex>", "strokeWidth": <number>, "opacity": <0-1>, "rotation": <degrees>, "scaleX", "scaleY", "cornerRadius": <number> (Rect and Image)

1. **Rect** - className: "Rect"
   Attrs: width, height. Optional: cornerRadius (for rounded rects)

2. **Ellipse** - className: "Ellipse"
   Attrs: radiusX, radiusY (NOT width/height)

3. **Text** - className: "Text"
   Attrs: text, fontSize (16-400), fontStyle ("<normal|italic> <100-900>"), fontFamily ("<Google Font>")
   Optional: align, letterSpacing, lineHeight, width (for wrapping), textDecoration ("underline"|"line-through")
   All Google Fonts available. Use diverse fonts: serif, sans-serif, display, handwritten.

4. **Image** - className: "Image"
   Attrs: width, height, fillPriority: "pattern", fillCover: true, fillPatternRepeat: "no-repeat",
   placeholderPrompt: "<vivid AI image generation prompt>". Optional: cornerRadius (for rounded image frames)

5. **Star** - className: "Star"
   Attrs: numPoints (3-12), innerRadius, outerRadius

6. **RegularPolygon** - className: "RegularPolygon"
   Attrs: radius, sides (3-12)

7. **Arc** - className: "Arc"
   Attrs: angle (1-360), innerRadius, outerRadius

8. **Ring** - className: "Ring"
   Attrs: innerRadius, outerRadius

9. **Wedge** - className: "Wedge"
   Attrs: radius, angle

10. **Path** - className: "Path"
    Attrs: data: "<SVG path string>", scaleX, scaleY
    Use for custom decorative shapes: arrows, badges, frames, swooshes, abstract blobs.

11. **TextPath** - className: "TextPath"
    Attrs: text, fontSize, fontFamily, data: "<SVG path string for curve>"
    For curved/circular text. Example circular path: "M200 100C200 155 155 200 100 200C45 200 0 155 0 100C0 45 45 0 100 0C155 0 200 45 200 100Z"

All Google Fonts are available. Match fonts to the design mood: bold sans-serifs for modern/tech, elegant serifs for classic, playful handwritten for casual, distinctive display fonts for headings.

DESIGN RULES:
- The first node MUST be the background covering the full stage (Rect or Image with x:0, y:0, matching stage dimensions)
- Layer nodes from back to front (background first, foreground text last)
- Use a cohesive color palette of 3-5 colors, hex only (e.g. "#ff5500")
- Font size hierarchy: main headings 80-300px, subheadings 40-80px, body text 20-40px
- Use the exact stage dimensions provided
- All x, y coordinates must be within stage bounds
- For multi-line text, use \n in the text string and set a width attr for wrapping
- Include meaningful placeholder text relevant to the description (not "Lorem ipsum")
- Use 1-2 Image nodes max for main visual areas

VISUAL RICHNESS — You MUST use a variety of node types, not just Rect and Text:
- Use Ellipse for circular frames, decorative circles, or clipping shapes behind images
- Use Rect with cornerRadius for rounded cards, buttons, badges, or banner strips
- Use Rect with low opacity as overlay layers on images for text readability
- Use Star for decorative accents, ratings, or bullet points
- Use Path for swooshes, curved dividers, abstract blobs, or custom decorative shapes
- Use TextPath for curved text along arcs (event dates, taglines, badges)
- Use stroke and strokeWidth generously: frame images with borders, add outlines to shapes, create bordered text boxes
- Every template should have at least 1-2 elements with visible strokes (strokeWidth 2-8)
- Use rotation on decorative elements for dynamic angles
- Aim for 8-15 nodes with at least 3 different className types

Respond with ONLY the JSON object. No other text.
PROMPT;
    }

    /**
     * @return array<array{node_id: string, task_id: int}>
     */
    public function submitPlaceholderImageGenerations(array &$templateData): array
    {
        $modelSlug = setting('creative_suite_template_image_model', 'gpt-image-1');
        $isGptImage = str_starts_with($modelSlug, 'gpt-image');
        $imageTasks = [];

        foreach ($templateData['nodes'] as $index => $nodeJson) {
            $node = json_decode($nodeJson, true);

            if (! $node || empty($node['attrs']['placeholderPrompt'])) {
                continue;
            }

            $prompt = $node['attrs']['placeholderPrompt'];
            $nodeId = $node['attrs']['id'] ?? null;

            if (! $nodeId) {
                continue;
            }

            try {
                if ($isGptImage) {
                    $path = $this->generateGptImage($prompt, $modelSlug);

                    if ($path) {
                        $node['attrs']['fillSource'] = url($path);
                        $templateData['nodes'][$index] = json_encode($node);
                    }
                } else {
                    $task = $this->submitFalAiImage($prompt, $modelSlug);

                    if ($task) {
                        $imageTasks[] = [
                            'node_id' => $nodeId,
                            'task_id' => $task,
                        ];
                    }
                }
            } catch (Exception $e) {
                Log::warning('Template placeholder image generation failed', [
                    'node_id' => $nodeId,
                    'prompt'  => $prompt,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $imageTasks;
    }

    private function buildTemplateUserMessage(TemplateGenerationRequest $request, array $dimensions): string
    {
        $description = $request->validated('template_description');
        $message = "Create a {$description} template. Stage dimensions: {$dimensions['width']}x{$dimensions['height']} pixels.";

        if ($request->hasFile('template_reference')) {
            $message .= ' Use the provided reference image as inspiration for the layout, color scheme, and overall style. Replicate its structure closely while incorporating the described content.';
        }

        return $message;
    }

    private function generateGptImage(string $prompt, string $model): ?string
    {
        $result = app(CreateImageService::class)
            ->setModel($model)
            ->setPrompt($prompt)
            ->generate();

        if ($result['status'] ?? false) {
            return $result['path'];
        }

        return null;
    }

    private function submitFalAiImage(string $prompt, string $modelSlug): ?int
    {
        $imageEntity = EntityEnum::fromSlug($modelSlug);
        $imageDriver = Entity::driver($imageEntity)->inputImageCount(1)->calculateCredit();
        $imageDriver->redirectIfNoCreditBalance();

        $requestId = FalAIService::generate($prompt, $imageEntity);

        $openai = OpenAIGenerator::query()->where('slug', 'ai_image_generator')->first();

        $userOpenai = UserOpenai::query()->create([
            'request_id' => $requestId,
            'team_id'    => Auth::user()?->teamId(),
            'title'      => Str::limit($prompt, 20),
            'slug'       => Str::random(7) . Str::slug(Auth::user()?->fullName()) . '-workbook',
            'user_id'    => Auth::id(),
            'openai_id'  => $openai?->getKey(),
            'input'      => $prompt,
            'response'   => 'CD',
            'output'     => '',
            'hash'       => Str::random(256),
            'credits'    => 1,
            'words'      => 0,
            'storage'    => 'public',
            'payload'    => [
                'taskId' => $requestId,
                'tool'   => 'template_image',
                'model'  => $imageEntity->value,
            ],
            'status' => 'IN_QUEUE',
            'model'  => $imageEntity->value,
            'engine' => $imageEntity->engine()?->value,
        ]);

        Usage::getSingle()->updateImageCounts($imageDriver->calculate());
        $imageDriver->decreaseCredit();

        return $userOpenai->getKey();
    }

    /**
     * Sanitize fontStyle to Konva's expected "normal|italic WEIGHT" format.
     */
    private function sanitizeFontStyle(string $fontStyle): string
    {
        $parts = preg_split('/\s+/', trim($fontStyle));
        $style = 'normal';
        $weight = '400';

        foreach ($parts as $part) {
            if (in_array($part, ['italic', 'oblique'], true)) {
                $style = $part;
            } elseif (is_numeric($part)) {
                $weight = $part;
            } elseif ($part === 'bold') {
                $weight = '700';
            }
        }

        return "{$style} {$weight}";
    }

    private function errorResponse(string $message): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'type'    => 'error',
            'message' => $message,
        ]);
    }
}
