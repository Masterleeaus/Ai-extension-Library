<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Deployments;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\TitanAI\Contracts\AssistantDeploymentResolverContract;
use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;
use Illuminate\Support\Arr;

final class AssistantDeploymentResolver implements AssistantDeploymentResolverContract
{
    public function resolve(TitanAIRequest $request): array
    {
        $payloadDeployment = (array) Arr::get($request->payload, 'assistant_deployment', []);
        if ($payloadDeployment !== []) {
            return $this->normalise($payloadDeployment, $request);
        }

        $chatbot = $this->resolveChatbot($request);
        if ($chatbot) {
            return $this->normalise([
                'id' => $request->assistantDeploymentId ?: 'chatbot-'.$chatbot->getKey(),
                'name' => (string) ($chatbot->name ?? 'Titan Zero Assistant'),
                'system_prompt' => (string) ($chatbot->instructions ?? ''),
                'provider' => (string) ($chatbot->ai_provider ?? $chatbot->provider ?? ''),
                'model' => (string) ($chatbot->ai_model ?? $chatbot->model ?? ''),
                'capabilities' => (array) ($chatbot->capabilities ?? []),
                'source' => 'chatbot',
                'source_id' => $chatbot->getKey(),
            ], $request);
        }

        return $this->normalise([
            'id' => $request->assistantDeploymentId ?: 'titan-zero-default',
            'name' => 'Titan Zero',
            'system_prompt' => (string) config('titan-ai.default_system_prompt', 'You are Titan Zero, a capable business assistant.'),
            'provider' => (string) config('titan-ai.default_provider', 'openai'),
            'model' => (string) config('titan-ai.default_model', config('openai.model', 'gpt-4o-mini')),
            'capabilities' => (array) config('titan-ai.default_capabilities', []),
            'source' => 'default',
        ], $request);
    }

    private function resolveChatbot(TitanAIRequest $request): ?Chatbot
    {
        $id = $request->payload['chatbot_id'] ?? null;
        if (! $id || ! class_exists(Chatbot::class)) {
            return null;
        }

        return Chatbot::query()->whereKey($id)->first();
    }

    private function normalise(array $deployment, TitanAIRequest $request): array
    {
        $channelCapabilities = (array) config('titan-ai.channel_capabilities.'.$request->channel, []);
        $requested = array_values(array_unique(array_filter([
            ...(array) ($deployment['capabilities'] ?? []),
            ...$channelCapabilities,
            ...$request->toolPermissions,
        ], 'is_string')));

        return [
            'id' => (string) ($deployment['id'] ?? $request->assistantDeploymentId ?? 'titan-zero-default'),
            'name' => (string) ($deployment['name'] ?? 'Titan Zero'),
            'system_prompt' => $this->nullable($deployment['system_prompt'] ?? null),
            'provider' => $this->nullable($deployment['provider'] ?? null),
            'model' => $this->nullable($deployment['model'] ?? null),
            'capabilities' => $requested,
            'metadata' => array_diff_key($deployment, array_flip(['id','name','system_prompt','provider','model','capabilities'])),
        ];
    }

    private function nullable(mixed $value): ?string
    {
        if (! is_scalar($value)) return null;
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
