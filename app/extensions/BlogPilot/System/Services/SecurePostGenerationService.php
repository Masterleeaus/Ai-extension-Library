<?php

declare(strict_types=1);

namespace App\Extensions\BlogPilot\System\Services;

use App\Extensions\BlogPilot\System\Models\BlogPilot;
use App\Helpers\Classes\ApiHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use Parsedown;
use Throwable;
use UnexpectedValueException;

class SecurePostGenerationService extends PostGenerationService
{
    private const MAX_TITLE_LENGTH = 500;

    private const MAX_CONTENT_LENGTH = 200000;

    private const MAX_TERM_LENGTH = 100;

    private const JSON_MAX_DEPTH = 8;

    public function generatePost(BlogPilot|Builder|Model $agent, int $postIndex = 0): array
    {
        try {
            ApiHelper::setOpenAiKey();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . ApiHelper::setOpenAiKey(),
                'Content-Type'  => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model'       => $this->model,
                'messages'    => $this->buildPrompt($agent, $postIndex),
                'temperature' => $this->getTemperature('high'),
                'max_tokens'  => 1000,
            ]);

            if ($response->failed()) {
                Log::error('BlogPilot post generation request failed', [
                    'status'     => $response->status(),
                    'request_id' => $response->header('x-request-id'),
                ]);

                return $this->failed('Failed to generate post');
            }

            $content = $response->json('choices.0.message.content');

            if (! is_string($content) || trim($content) === '') {
                Log::warning('BlogPilot post generation returned no model content', [
                    'request_id' => $response->header('x-request-id'),
                ]);

                return $this->failed('Invalid AI response format');
            }

            try {
                $decoded = json_decode(
                    $this->extractJsonDocument($content),
                    true,
                    self::JSON_MAX_DEPTH,
                    JSON_THROW_ON_ERROR
                );
            } catch (JsonException $exception) {
                Log::warning('BlogPilot post generation returned invalid JSON', [
                    'request_id' => $response->header('x-request-id'),
                    'json_error' => $exception->getMessage(),
                ]);

                return $this->failed('Invalid AI response format');
            }

            try {
                $post = $this->validateGeneratedPost($decoded);
            } catch (UnexpectedValueException $exception) {
                Log::warning('BlogPilot post generation failed schema validation', [
                    'request_id' => $response->header('x-request-id'),
                    'reason'     => $exception->getMessage(),
                ]);

                return $this->failed('Invalid AI response schema');
            }

            $parsedown = new Parsedown;

            if (method_exists($parsedown, 'setSafeMode')) {
                $parsedown->setSafeMode(true);
            }

            $post['post_content'] = $parsedown->text($post['post_content']);
            $post['success'] = true;

            if ($agent->has_image) {
                $image = $this->getImageService()->generateImageForPost($post['post_title']);

                if (isset($image['image_url']) && is_string($image['image_url'])) {
                    $post['image_url'] = ltrim($image['image_url'], '/');
                }
            }

            return $post;
        } catch (Throwable $exception) {
            Log::error('BlogPilot secure post generation failed', [
                'exception' => $exception::class,
                'error'     => $exception->getMessage(),
            ]);

            return $this->failed('Failed to generate post');
        }
    }

    private function extractJsonDocument(string $content): string
    {
        $trimmed = trim($content);
        $jsonStart = strpos($trimmed, '{');
        $jsonEnd = strrpos($trimmed, '}');

        if ($jsonStart === false || $jsonEnd === false || $jsonEnd <= $jsonStart) {
            throw new UnexpectedValueException('Model output did not contain a JSON object.');
        }

        return substr($trimmed, $jsonStart, $jsonEnd - $jsonStart + 1);
    }

    /**
     * @return array{post_title:string, post_content:string, post_tags:list<string>, post_categories:list<string>}
     */
    private function validateGeneratedPost(mixed $post): array
    {
        if (! is_array($post)) {
            throw new UnexpectedValueException('Generated post must be an object.');
        }

        $title = $this->requireBoundedString($post['post_title'] ?? null, self::MAX_TITLE_LENGTH, 'post_title');
        $content = $this->requireBoundedString($post['post_content'] ?? null, self::MAX_CONTENT_LENGTH, 'post_content');
        $tags = $this->requireStringList($post['post_tags'] ?? null, 3, 'post_tags');
        $categories = $this->requireStringList($post['post_categories'] ?? null, 1, 'post_categories');

        if (count($tags) !== 3) {
            throw new UnexpectedValueException('post_tags must contain exactly three strings.');
        }

        if (count($categories) !== 1) {
            throw new UnexpectedValueException('post_categories must contain exactly one string.');
        }

        return [
            'post_title'      => $title,
            'post_content'    => $content,
            'post_tags'       => $tags,
            'post_categories' => $categories,
        ];
    }

    private function requireBoundedString(mixed $value, int $maxLength, string $field): string
    {
        if (! is_string($value)) {
            throw new UnexpectedValueException("{$field} must be a string.");
        }

        $value = trim($value);

        if ($value === '' || strlen($value) > $maxLength) {
            throw new UnexpectedValueException("{$field} is empty or too long.");
        }

        return $value;
    }

    /** @return list<string> */
    private function requireStringList(mixed $value, int $expectedCount, string $field): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) !== $expectedCount) {
            throw new UnexpectedValueException("{$field} has an invalid item count.");
        }

        $items = [];

        foreach ($value as $item) {
            $items[] = $this->requireBoundedString($item, self::MAX_TERM_LENGTH, $field);
        }

        return $items;
    }

    private function failed(string $message): array
    {
        return [
            'success' => false,
            'error'   => $message,
        ];
    }
}
