<?php

declare(strict_types=1);

namespace App\Extensions\CreativeSuiteAnnotations\System\Services;

use App\Domains\Engine\Enums\EngineEnum;
use App\Domains\Entity\Enums\EntityEnum;
use App\Helpers\Classes\ApiHelper;
use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Runs the "Replace Text" tool's vision-analyse step. Given a source image,
 * asks a vision-capable LLM to return every visible text block as
 * `{text, x, y, width, height}` in source-image pixel coordinates so the
 * frontend can drop a numbered comment-style pin at each block's centre.
 *
 * For OpenAI we use the Chat Completions API with structured JSON output
 * (`response_format: json_schema`). Non-OpenAI providers fall back to a
 * "ask for JSON in the prompt and parse" path, since not every provider
 * exposes strict structured output through the same surface.
 */
class CreativeSuiteAnnotationsVisionService
{
    /**
     * Cap the long edge of the image we send to the vision model. Keeps
     * vision-token usage bounded (1024-long-edge is ~2-4 OpenAI tiles vs.
     * 16+ for a 4K image) and — combined with normalised coordinates —
     * eliminates the silent-resize ambiguity that causes vertical drift on
     * tall images: we control exactly what the model sees, and we ask for
     * coordinates back in a resolution-independent space.
     */
    private const VISION_LONG_EDGE_PX = 1024;

    private const VISION_INSTRUCTION = <<<'TXT'
You are a precise OCR and layout analyser. Look at the supplied image and
return EVERY visible text block as a JSON object matching the provided
schema. Each block is one line or a tightly grouped set of words that
share a baseline and styling.

Bounding boxes use a NORMALISED coordinate system. Treat the image as a
1000-by-1000 grid regardless of its actual pixel size: x=0 is the left
edge, x=1000 is the right edge, y=0 is the top edge, y=1000 is the
bottom edge. `x` / `y` are the top-left of the block, `width` / `height`
are the block's dimensions, all as integers in the range 0..1000. The
box must fit inside the grid (x + width <= 1000, y + height <= 1000).

If you are uncertain about a block, omit it. Do not invent text. Do not
include duplicate boxes.
TXT;

    /**
     * @return array{blocks: list<array{text: string, x: int, y: int, width: int, height: int}>}
     */
    public static function analyze(UploadedFile $image, EntityEnum $entity): array
    {
        // Pre-downscale large images server-side so we control exactly what
        // the vision model receives (the model would silently downscale
        // anyway on its end, in a coordinate space we don't know about).
        $payloadBytes = self::prepareImagePayload($image);
        $base64 = base64_encode($payloadBytes);
        $mime = 'image/png';

        return match ($entity->engine()) {
            EngineEnum::OPEN_AI   => self::analyzeViaOpenAI($entity, $base64, $mime),
            EngineEnum::ANTHROPIC => self::analyzeViaAnthropic($entity, $base64, $mime),
            EngineEnum::GEMINI    => self::analyzeViaGemini($entity, $base64, $mime),
            EngineEnum::X_AI      => self::analyzeViaXAI($entity, $base64, $mime),
            default               => self::analyzeViaGenericChat($entity, $base64, $mime),
        };
    }

    /**
     * Resize the source image so its long edge is at most VISION_LONG_EDGE_PX,
     * preserving aspect ratio. Always returns PNG bytes. Below the threshold
     * the original bytes are returned verbatim (no recompression).
     */
    private static function prepareImagePayload(UploadedFile $image): string
    {
        $manager = ImageManager::gd();
        $img = $manager->read($image->getRealPath());

        $width = $img->width();
        $height = $img->height();
        $longEdge = max($width, $height);

        if ($longEdge <= self::VISION_LONG_EDGE_PX) {
            return (string) file_get_contents($image->getRealPath());
        }

        // Intervention v3: scale() preserves aspect ratio when only one
        // dimension is supplied.
        if ($width >= $height) {
            $img->scale(width: self::VISION_LONG_EDGE_PX);
        } else {
            $img->scale(height: self::VISION_LONG_EDGE_PX);
        }

        return (string) $img->toPng();
    }

    /**
     * @return array{blocks: list<array{text: string, x: int, y: int, width: int, height: int}>}
     */
    private static function analyzeViaOpenAI(EntityEnum $entity, string $base64, string $mime): array
    {
        $apiKey = ApiHelper::setOpenAiKey();
        $payload = self::buildOpenAICompatiblePayload($entity, $base64, $mime);

        $client = new Client;
        $response = $client->post('https://api.openai.com/v1/chat/completions', [
            'json'            => $payload,
            'timeout'         => 120,
            'connect_timeout' => 30,
            'http_errors'     => false,
            'headers'         => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ],
        ]);

        return self::parseOpenAICompatibleResponse($response);
    }

    /**
     * OpenAI Chat Completions request body — the same shape works for any
     * OpenAI-compatible endpoint (OpenAI proper and xAI Grok).
     */
    private static function buildOpenAICompatiblePayload(EntityEnum $entity, string $base64, string $mime): array
    {
        return [
            'model'    => $entity->value,
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => self::VISION_INSTRUCTION,
                ],
                [
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'List every text block in this image as JSON. Use the 0..1000 normalised grid for coordinates as instructed.'],
                        [
                            'type'      => 'image_url',
                            'image_url' => [
                                'url'    => "data:{$mime};base64,{$base64}",
                                // Force "high" tile mode — we already capped the long edge at
                                // 1024 server-side so the cost stays bounded, but we want the
                                // fine-detail tiles for accurate text grounding.
                                'detail' => 'high',
                            ],
                        ],
                    ],
                ],
            ],
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => [
                    'name'   => 'text_blocks',
                    'strict' => true,
                    'schema' => [
                        'type'                 => 'object',
                        'additionalProperties' => false,
                        'properties'           => [
                            'blocks' => [
                                'type'  => 'array',
                                'items' => [
                                    'type'                 => 'object',
                                    'additionalProperties' => false,
                                    'properties'           => [
                                        'text'   => ['type' => 'string', 'description' => 'The visible text in the block, exactly as it appears.'],
                                        'x'      => ['type' => 'integer', 'description' => 'Top-left X on the 0..1000 normalised grid.'],
                                        'y'      => ['type' => 'integer', 'description' => 'Top-left Y on the 0..1000 normalised grid.'],
                                        'width'  => ['type' => 'integer', 'description' => 'Block width on the 0..1000 normalised grid.'],
                                        'height' => ['type' => 'integer', 'description' => 'Block height on the 0..1000 normalised grid.'],
                                    ],
                                    'required' => ['text', 'x', 'y', 'width', 'height'],
                                ],
                            ],
                        ],
                        'required' => ['blocks'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Decode an OpenAI-compatible Chat Completions response (used for both
     * OpenAI and xAI). Surfaces upstream errors with the provider's message.
     *
     * @return array{blocks: list<array{text: string, x: int, y: int, width: int, height: int}>}
     */
    private static function parseOpenAICompatibleResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $json = json_decode($body, true);

        if ($response->getStatusCode() >= 400) {
            $message = data_get($json, 'error.message') ?: $body;

            throw new RuntimeException(__('Vision model error: :msg', ['msg' => $message]));
        }

        $content = data_get($json, 'choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new RuntimeException(__('Vision model returned no content.'));
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            // xAI / OpenAI-compatible providers occasionally return prose
            // around the JSON if json_schema isn't fully honoured.
            $decoded = self::extractFirstJsonObject($content);
        }

        return self::normalizeBlocks($decoded);
    }

    /**
     * Anthropic Claude — image content block + ask for JSON in the user
     * message. Anthropic doesn't expose strict response_format, so we wrap
     * with explicit "respond with JSON only" instructions and use the
     * extractFirstJsonObject helper to recover the JSON from the reply.
     *
     * @return array{blocks: list<array{text: string, x: int, y: int, width: int, height: int}>}
     */
    private static function analyzeViaAnthropic(EntityEnum $entity, string $base64, string $mime): array
    {
        $apiKey = ApiHelper::setAnthropicKey();

        $payload = [
            'model'      => $entity->value,
            'max_tokens' => 4096,
            'system'     => self::VISION_INSTRUCTION . "\n\n" .
                'Respond with a single JSON object only — no prose, no code fences. ' .
                'Shape: {"blocks":[{"text":"...","x":0,"y":0,"width":0,"height":0}, ...]}.',
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $mime,
                                'data'       => $base64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'List every text block in this image as JSON using the 0..1000 normalised grid.',
                        ],
                    ],
                ],
            ],
        ];

        $client = new Client;
        $response = $client->post('https://api.anthropic.com/v1/messages', [
            'json'            => $payload,
            'timeout'         => 120,
            'connect_timeout' => 30,
            'http_errors'     => false,
            'headers'         => [
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ],
        ]);

        $body = (string) $response->getBody();
        $json = json_decode($body, true);

        if ($response->getStatusCode() >= 400) {
            $message = data_get($json, 'error.message') ?: $body;

            throw new RuntimeException(__('Vision model error: :msg', ['msg' => $message]));
        }

        // Anthropic returns content as an array of blocks; the text block(s)
        // are flattened into one string before JSON-parsing.
        $text = '';
        foreach ((array) data_get($json, 'content', []) as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
        }

        $decoded = self::extractFirstJsonObject($text);

        return self::normalizeBlocks($decoded);
    }

    /**
     * Google Gemini — native structured output via responseSchema and
     * responseMimeType=application/json. The schema mirrors the OpenAI one
     * but uses Gemini's openapi-3-style type names (no `additionalProperties`).
     *
     * @return array{blocks: list<array{text: string, x: int, y: int, width: int, height: int}>}
     */
    private static function analyzeViaGemini(EntityEnum $entity, string $base64, string $mime): array
    {
        $apiKey = ApiHelper::setGeminiKey();
        $model = $entity->value;

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => self::VISION_INSTRUCTION]],
            ],
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [
                        ['text' => 'List every text block in this image as JSON using the 0..1000 normalised grid.'],
                        ['inlineData' => ['mimeType' => $mime, 'data' => $base64]],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema'   => [
                    'type'       => 'OBJECT',
                    'properties' => [
                        'blocks' => [
                            'type'  => 'ARRAY',
                            'items' => [
                                'type'       => 'OBJECT',
                                'properties' => [
                                    'text'   => ['type' => 'STRING'],
                                    'x'      => ['type' => 'INTEGER'],
                                    'y'      => ['type' => 'INTEGER'],
                                    'width'  => ['type' => 'INTEGER'],
                                    'height' => ['type' => 'INTEGER'],
                                ],
                                'required' => ['text', 'x', 'y', 'width', 'height'],
                            ],
                        ],
                    ],
                    'required' => ['blocks'],
                ],
            ],
        ];

        $client = new Client;
        $response = $client->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'json'            => $payload,
                'timeout'         => 120,
                'connect_timeout' => 30,
                'http_errors'     => false,
                'headers'         => ['Content-Type' => 'application/json'],
            ]
        );

        $body = (string) $response->getBody();
        $json = json_decode($body, true);

        if ($response->getStatusCode() >= 400) {
            $message = data_get($json, 'error.message') ?: $body;

            throw new RuntimeException(__('Vision model error: :msg', ['msg' => $message]));
        }

        $text = (string) data_get($json, 'candidates.0.content.parts.0.text', '');
        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            // The model occasionally wraps the JSON in code fences despite
            // responseMimeType — fall back to the same scavenger we use
            // for Anthropic.
            $decoded = self::extractFirstJsonObject($text);
        }

        return self::normalizeBlocks($decoded);
    }

    /**
     * xAI Grok — fully OpenAI-compatible chat completions surface, so we
     * reuse the OpenAI request shape (json_schema response_format) and only
     * swap the endpoint and API key.
     *
     * @return array{blocks: list<array{text: string, x: int, y: int, width: int, height: int}>}
     */
    private static function analyzeViaXAI(EntityEnum $entity, string $base64, string $mime): array
    {
        $apiKey = ApiHelper::setXAiKey();

        $payload = self::buildOpenAICompatiblePayload($entity, $base64, $mime);

        $client = new Client;
        $response = $client->post('https://api.x.ai/v1/chat/completions', [
            'json'            => $payload,
            'timeout'         => 120,
            'connect_timeout' => 30,
            'http_errors'     => false,
            'headers'         => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ],
        ]);

        return self::parseOpenAICompatibleResponse($response);
    }

    /**
     * Last-resort fallback for any provider not explicitly handled. Surfaces
     * a clean error rather than silently calling the wrong endpoint.
     *
     * @return array{blocks: list<array{text: string, x: int, y: int, width: int, height: int}>}
     */
    private static function analyzeViaGenericChat(EntityEnum $entity, string $base64, string $mime): array
    {
        throw new RuntimeException(__(
            'The configured vision model (:model) is not supported yet.',
            ['model' => $entity->value]
        ));
    }

    /**
     * Find the first balanced top-level JSON object in a string and decode
     * it. Handles common LLM patterns (`leading prose ... { ... } trailing`,
     * code fences). Returns null on failure.
     */
    private static function extractFirstJsonObject(string $text): ?array
    {
        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escape = false;

        for ($i = $start, $len = strlen($text); $i < $len; $i++) {
            $ch = $text[$i];

            if ($inString) {
                if ($escape) {
                    $escape = false;
                } elseif ($ch === '\\') {
                    $escape = true;
                } elseif ($ch === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($ch === '"') {
                $inString = true;
            } elseif ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    $candidate = substr($text, $start, $i - $start + 1);
                    $decoded = json_decode($candidate, true);

                    return is_array($decoded) ? $decoded : null;
                }
            }
        }

        return null;
    }

    /**
     * Coordinates are integers on the 0..1000 normalised grid (see
     * VISION_INSTRUCTION). Clamp to the grid here so a model that goes
     * slightly out of bounds doesn't break the frontend's remap.
     *
     * @return array{blocks: list<array{text: string, x: int, y: int, width: int, height: int}>}
     */
    private static function normalizeBlocks(mixed $decoded): array
    {
        if (! is_array($decoded) || ! is_array($decoded['blocks'] ?? null)) {
            return ['blocks' => []];
        }

        $blocks = [];

        foreach ($decoded['blocks'] as $block) {
            if (! is_array($block)) {
                continue;
            }

            $text = trim((string) ($block['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $blocks[] = [
                'text'   => $text,
                'x'      => self::clampGrid((int) ($block['x'] ?? 0)),
                'y'      => self::clampGrid((int) ($block['y'] ?? 0)),
                'width'  => self::clampGrid((int) ($block['width'] ?? 0)),
                'height' => self::clampGrid((int) ($block['height'] ?? 0)),
            ];
        }

        return ['blocks' => $blocks];
    }

    private static function clampGrid(int $v): int
    {
        return max(0, min(1000, $v));
    }
}
