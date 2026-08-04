<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Inference;

use App\Extensions\TitanAIGovernance\System\Contracts\ModelInferenceClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final readonly class OpenAIResponsesClient implements ModelInferenceClient
{
    public function __construct(private ModelBudgetGuard $budget) {}

    public function structured(string $model, array $input, array $schema, array $options = []): array
    {
        $apiKey = (string) config('titan-ai-governance.openai.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $timeout = max(1, (int) ($options['timeout_seconds'] ?? config('titan-ai-governance.openai.timeout_seconds', 30)));
        $retries = max(0, (int) ($options['retries'] ?? config('titan-ai-governance.openai.retries', 2)));
        $endpoint = rtrim((string) config('titan-ai-governance.openai.base_url', 'https://api.openai.com/v1'), '/').'/responses';
        $payload = [
            'model' => $model,
            'store' => false,
            'input' => [[
                'role' => 'user',
                'content' => [['type' => 'input_text', 'text' => json_encode($input, JSON_THROW_ON_ERROR)]],
            ]],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'titan_council_review',
                    'strict' => true,
                    'schema' => $schema,
                ],
                'verbosity' => 'low',
            ],
        ];

        $request = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout($timeout)
            ->retry($retries, 250, throw: false);
        $this->applyOptionalHeaders($request);

        $response = $request->post($endpoint, $payload);
        if (! $response->successful()) {
            throw new RuntimeException('OpenAI council review failed with HTTP '.$response->status().'.');
        }

        $body = $response->json();
        if (! is_array($body) || ($body['status'] ?? 'completed') !== 'completed') {
            throw new RuntimeException('OpenAI council review did not complete.');
        }

        $text = $this->extractOutputText($body);
        $data = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($data)) {
            throw new RuntimeException('OpenAI council review returned an invalid structured payload.');
        }

        $inputTokens = (int) ($body['usage']['input_tokens'] ?? 0);
        $outputTokens = (int) ($body['usage']['output_tokens'] ?? 0);
        $cost = $this->budget->assertAllowed($model, $inputTokens, $outputTokens);

        return [
            'data' => $data,
            'meta' => [
                'provider' => 'openai',
                'model' => (string) ($body['model'] ?? $model),
                'response_id' => (string) ($body['id'] ?? ''),
                'request_id' => (string) ($response->header('x-request-id') ?? ''),
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $inputTokens + $outputTokens,
                'cost' => $cost,
            ],
        ];
    }

    private function extractOutputText(array $body): string
    {
        if (isset($body['output_text']) && is_string($body['output_text'])) {
            return $body['output_text'];
        }
        foreach ((array) ($body['output'] ?? []) as $item) {
            foreach ((array) ($item['content'] ?? []) as $content) {
                if (($content['type'] ?? '') === 'output_text' && is_string($content['text'] ?? null)) {
                    return $content['text'];
                }
                if (($content['type'] ?? '') === 'refusal') {
                    throw new RuntimeException('OpenAI reviewer refused the council request.');
                }
            }
        }
        throw new RuntimeException('OpenAI response contained no structured output text.');
    }

    private function applyOptionalHeaders(PendingRequest $request): void
    {
        $organisation = (string) config('titan-ai-governance.openai.organisation', '');
        $project = (string) config('titan-ai-governance.openai.project', '');
        if ($organisation !== '') {
            $request->withHeaders(['OpenAI-Organization' => $organisation]);
        }
        if ($project !== '') {
            $request->withHeaders(['OpenAI-Project' => $project]);
        }
    }
}
