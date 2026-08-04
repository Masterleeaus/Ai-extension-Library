<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

use App\Extensions\Chatbot\System\TitanAI\Contracts\GeneralConversationEngineContract;
use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class LaravelGeneralConversationEngine implements GeneralConversationEngineContract
{
    public function complete(TitanAIRequest $request, array $runtimeContext = []): array
    {
        if (app()->bound('titan.ai.general_conversation')) {
            $engine = app('titan.ai.general_conversation');
            $result = is_callable($engine) ? $engine($request, $runtimeContext) : $engine->complete($request, $runtimeContext);
            return $this->normalise($result, $runtimeContext);
        }

        foreach ((array) config('titan-ai.compatible_completion_services', []) as $class) {
            if (! is_string($class) || ! class_exists($class)) continue;
            $service = app($class);
            foreach (['complete','chat','generate'] as $method) {
                if (! method_exists($service, $method)) continue;
                try {
                    return $this->normalise($service->{$method}($request, $runtimeContext), $runtimeContext);
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return $this->openAICompatible($request, $runtimeContext);
    }

    private function openAICompatible(TitanAIRequest $request, array $runtimeContext): array
    {
        $deployment = (array) ($runtimeContext['deployment'] ?? []);
        $endpoint = (string) ($request->payload['provider_endpoint'] ?? config('titan-ai.openai_compatible.endpoint', 'https://api.openai.com/v1/chat/completions'));
        $key = (string) ($request->payload['provider_key'] ?? config('titan-ai.openai_compatible.key', config('services.openai.key', env('OPENAI_API_KEY'))));
        $model = (string) ($deployment['model'] ?? config('titan-ai.default_model', 'gpt-4o-mini'));

        if (trim($key) === '') {
            throw new RuntimeException('No general-conversation provider is configured for the shared Titan AI runtime.');
        }

        $messages = (array) ($runtimeContext['messages'] ?? []);
        if (($deployment['system_prompt'] ?? null) !== null) {
            array_unshift($messages, ['role' => 'system', 'content' => $deployment['system_prompt']]);
        }
        $messages[] = ['role' => 'user', 'content' => $request->message];

        $response = Http::withToken($key)
            ->acceptJson()
            ->timeout((int) config('titan-ai.openai_compatible.timeout', 120))
            ->post($endpoint, [
                'model' => $model,
                'messages' => $messages,
                'temperature' => (float) ($request->payload['temperature'] ?? config('titan-ai.temperature', 0.7)),
                'tools' => $request->payload['tools'] ?? null,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Shared AI provider request failed with HTTP '.$response->status().'.');
        }

        return [
            'message' => (string) ($response->json('choices.0.message.content') ?? ''),
            'provider' => (string) ($deployment['provider'] ?? 'openai-compatible'),
            'model' => $model,
            'usage' => (array) $response->json('usage', []),
            'raw' => $response->json(),
        ];
    }

    private function normalise(mixed $result, array $runtimeContext): array
    {
        if (is_string($result)) $result = ['message' => $result];
        if (is_object($result) && method_exists($result, 'toArray')) $result = $result->toArray();
        if (! is_array($result)) throw new RuntimeException('The configured general-conversation engine returned an unsupported response.');

        return [
            'message' => (string) ($result['message'] ?? $result['text'] ?? $result['content'] ?? ''),
            'provider' => $result['provider'] ?? ($runtimeContext['deployment']['provider'] ?? null),
            'model' => $result['model'] ?? ($runtimeContext['deployment']['model'] ?? null),
            'usage' => (array) ($result['usage'] ?? []),
            'citations' => (array) ($result['citations'] ?? []),
            'generative_ui' => $result['generative_ui'] ?? null,
            'tool_calls' => (array) ($result['tool_calls'] ?? []),
            'raw' => $result,
        ];
    }
}
