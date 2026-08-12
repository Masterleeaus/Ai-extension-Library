<?php

namespace App\Extensions\BlogPilot\System\Services;

use App\Domains\Entity\Enums\EntityEnum;
use App\Helpers\Classes\ApiHelper;
use App\Services\Security\RemoteImageFetcher;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageGenerationService
{
    protected string $model = 'fal-ai/flux-pro';

    protected string $falApiKey;

    public function __construct() {}

    /**
     * Generate an image using the existing synchronous FAL path.
     */
    public function generateImageForPost(string $postContent, array $options = []): array
    {
        $this->falApiKey = ApiHelper::setFalAIKey();

        try {
            $imagePrompt = $this->createImagePrompt($postContent, $options);
            $result = $this->submitToFalAi($imagePrompt, $options);

            if (! $result['success']) {
                return [
                    'success' => false,
                    'error'   => $result['error'] ?? 'Failed to generate image',
                ];
            }

            return [
                'success'      => true,
                'request_id'   => $result['request_id'] ?? null,
                'prompt'       => $imagePrompt,
                'status'       => $result['status'] ?? 'completed',
                'image_url'    => $result['image_url'] ?? null,
                'submitted_at' => now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            Log::error('ImageGenerationService error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Submit a direct synchronous image generation request to FAL.
     */
    protected function submitToFalAi(string $prompt, array $options = []): array
    {
        $this->falApiKey = ApiHelper::setFalAIKey();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Key ' . $this->falApiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(120)->post('https://fal.run/fal-ai/flux-pro', [
                'prompt'                => $prompt,
                'image_size'            => $options['image_size'] ?? 'landscape_4_3',
                'num_inference_steps'   => 28,
                'guidance_scale'        => 3.5,
                'num_images'            => 1,
                'enable_safety_checker' => true,
                'output_format'         => 'jpeg',
            ]);

            if ($response->failed()) {
                Log::error('Fal.ai image generation failed', [
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'error'   => 'Fal.ai API request failed',
                ];
            }

            $data = $response->json();
            $imageUrl = $data['images'][0]['url'] ?? null;

            if (! is_string($imageUrl) || trim($imageUrl) === '') {
                Log::warning('Fal.ai image generation returned no image', [
                    'request_id' => $response->header('x-fal-request-id'),
                ]);

                return [
                    'success' => false,
                    'error'   => 'Fal.ai returned no image',
                ];
            }

            $result = [
                'success'    => true,
                'request_id' => $response->header('x-fal-request-id'),
                'status'     => 'completed',
            ];

            $result['image_url'] = $this->downloadAndStoreImage($imageUrl);

            return $result;
        } catch (Exception $e) {
            Log::error('Fal.ai submission error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Poll FAL queue status when a queue request ID is supplied externally.
     */
    public function checkStatus(string $requestId): array
    {
        $this->falApiKey = ApiHelper::setFalAIKey();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Key ' . $this->falApiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(10)->get("https://queue.fal.run/fal-ai/flux-pro/requests/{$requestId}/status");

            if ($response->failed()) {
                return [
                    'success' => false,
                    'status'  => 'failed',
                ];
            }

            $data = $response->json();

            if (($data['status'] ?? null) === 'COMPLETED') {
                $imageUrl = $data['images'][0]['url'] ?? null;

                if (is_string($imageUrl) && $imageUrl !== '') {
                    $storedPath = $this->downloadAndStoreImage($imageUrl);

                    return [
                        'success'   => true,
                        'status'    => 'completed',
                        'image_url' => $storedPath,
                    ];
                }
            }

            return [
                'success' => true,
                'status'  => strtolower((string) ($data['status'] ?? 'pending')),
            ];
        } catch (Exception $e) {
            Log::error('Fal.ai status check error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status'  => 'error',
            ];
        }
    }

    /**
     * Create an image prompt from post content
     */
    protected function createImagePrompt(string $postContent, array $options = []): string
    {
        try {
            ApiHelper::setOpenAiKey();

            $systemPrompt = <<<'SYSTEM'
You are an expert at creating image prompts for AI image generation.
Create a detailed, visual prompt for a blog post featured image.

Guidelines:
- Focus on visual elements, composition, colors, and style
- Make it relevant to the post content
- Keep it concise but descriptive (max 100 words)
- Avoid text, logos, or brand names in the image
- Create professional, eye-catching imagery
- Use modern, clean aesthetics
SYSTEM;

            $userPrompt = <<<PROMPT
Create an image generation prompt for this blog post title:

"{$postContent}"

Return only the image prompt, nothing else.
PROMPT;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . ApiHelper::setOpenAiKey(),
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model'       => EntityEnum::GPT_5_MINI->value,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role'    => 'user',
                        'content' => $userPrompt,
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens'  => 200,
            ]);

            if ($response->failed()) {
                Log::error('Failed to create image prompt', [
                    'status' => $response->status(),
                ]);

                return 'Professional image, modern design, clean composition, vibrant colors';
            }

            return trim((string) $response->json('choices.0.message.content'));
        } catch (Exception $e) {
            Log::warning('Error creating image prompt', [
                'error' => $e->getMessage(),
            ]);

            return 'Professional image, modern design, clean composition, vibrant colors';
        }
    }

    /**
     * Generate image using OpenAI image API fallback.
     */
    protected function generateWithFluxPro(string $prompt): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . ApiHelper::setOpenAiKey(),
                'Content-Type'  => 'application/json',
            ])->timeout(120)->post('https://api.openai.com/v1/images/generations', [
                'model'   => 'dall-e-3',
                'prompt'  => $prompt,
                'n'       => 1,
                'size'    => '1024x1024',
                'quality' => 'standard',
            ]);

            if ($response->failed()) {
                Log::error('OpenAI image generation failed', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $imageUrl = $response->json('data.0.url');

            return is_string($imageUrl) ? $imageUrl : null;
        } catch (Exception $e) {
            Log::error('OpenAI image generation error', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Download and store a generated image through the shared SSRF-safe fetcher.
     */
    protected function downloadAndStoreImage(string $url): string
    {
        $relativePath = app(RemoteImageFetcher::class)->store(
            $url,
            'public',
            'blogpilot'
        );

        return '/uploads/' . $relativePath;
    }

    /**
     * Set the AI model to use
     */
    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }
}
