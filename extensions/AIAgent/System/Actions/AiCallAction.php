<?php

declare(strict_types=1);

namespace App\Extensions\AIAgent\System\Actions;

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use App\Extensions\AIAgent\System\Actions\Contracts\AIAgentActionInterface;
use App\Extensions\AIAgent\System\Memory\MemoryInjector;
use App\Extensions\AIAgent\System\Memory\MemoryRepository;
use App\Extensions\AIAgent\System\Models\AIAgentKnowledgeSource;
use App\Extensions\AIAgent\System\Models\AIAgentMessage;
use App\Extensions\AIAgent\System\Models\AIAgentWorkflow;
use App\Extensions\AIAgent\System\Models\AIAgentWorkflowRun;
use App\Extensions\AIAgent\System\Services\AIAgentChatService;
use App\Helpers\Classes\Helper;
use App\Models\User;
use Exception;
use ValueError;

class AiCallAction implements AIAgentActionInterface
{
    public function __construct(
        private readonly AIAgentChatService $chatService,
        private readonly MemoryRepository $memoryRepository,
        private readonly MemoryInjector $memoryInjector,
    ) {}

    /**
     * Config keys:
     *   - system_prompt (string)   : Base system prompt
     *   - user_message (string)    : User message template (supports {context_key})
     *   - inject_memory (bool)     : Prepend user memory to system prompt (default: false)
     *   - history_turns (int)      : Previous message pairs to include as context (default: 10, 0 to disable)
     *   - store_output_as (string) : Context key to store the AI response (default: ai_response)
     *   - web_search (bool)        : Enable model-native web search (default: false)
     */
    public function execute(array $config, array $context, AIAgentWorkflow $workflow, AIAgentWorkflowRun $run): array
    {
        $systemPrompt = $config['system_prompt'] ?? 'You are a helpful AI assistant.';
        $userMessage = $this->interpolate($config['user_message'] ?? '', $context);
        $injectMemory = (bool) ($config['inject_memory'] ?? $context['inject_memory'] ?? false);
        $historyTurns = (int) ($config['history_turns'] ?? 10);
        $storeOutputAs = $config['store_output_as'] ?? 'ai_response';

        // Resolve optional model override
        $modelEnum = null;
        if (! empty($config['model'])) {
            try {
                $modelEnum = EntityEnum::from($config['model']);
            } catch (ValueError) {
                // Unknown model key — fall back to engine default
            }
        }

        if (empty($userMessage)) {
            throw new Exception('AiCallAction: user_message is required.');
        }

        $user = $workflow->user_id ? User::find($workflow->user_id) : null;

        if ($user) {
            $checkModel = $modelEnum ?? Helper::defaultWordModel();
            $driver = Entity::driver($checkModel)->forUser($user);

            if (! $driver->hasCreditBalance()) {
                throw new Exception('AiCallAction: insufficient credits to complete this request.');
            }
        }

        // Inject knowledge sources into system prompt
        $knowledgeSourceIds = $config['knowledge_source_ids'] ?? [];
        if (! empty($knowledgeSourceIds) && $workflow->user_id) {
            $sources = AIAgentKnowledgeSource::query()
                ->forUser($workflow->user_id)
                ->whereIn('id', $knowledgeSourceIds)
                ->get(['name', 'content']);

            if ($sources->isNotEmpty()) {
                $knowledgeBlock = "\n\n## Knowledge Base\n\n";
                foreach ($sources as $source) {
                    $knowledgeBlock .= "### {$source->name}\n{$source->content}\n\n";
                }
                $systemPrompt .= $knowledgeBlock;
            }
        }

        if ($injectMemory && $workflow->user_id) {
            $selectedIds = $config['selected_memory_ids'] ?? [];

            if (! empty($selectedIds)) {
                $memories = $this->memoryRepository->findByIds($workflow->user_id, $selectedIds);
                $systemPrompt = $this->memoryInjector->prepend($systemPrompt, $memories);
            }
        }

        $webSearch = (bool) ($config['web_search'] ?? false);

        $messages = $this->buildMessages($context, $userMessage, $historyTurns);
        $response = $this->chatService->forUser($user)->chat($systemPrompt, $messages, null, $modelEnum, $webSearch);

        if ($response === '') {
            throw new Exception('AiCallAction: AI returned an empty response. Check your model selection and API key, or try a different model.');
        }

        return array_merge($context, [$storeOutputAs => $response]);
    }

    /**
     * Build the messages array, prepending recent conversation history when available.
     * Produces multimodal content when the context contains `message_attachments`.
     *
     * @return array<int, array{role: string, content: string|array<int, mixed>}>
     */
    private function buildMessages(array $context, string $userMessage, int $historyTurns): array
    {
        $attachments = $context['message_attachments'] ?? [];
        $userContent = empty($attachments)
            ? $userMessage
            : $this->buildMultimodalContent($userMessage, $attachments);

        if ($historyTurns > 0) {
            $senderId = $context['sender_id'] ?? null;
            $channelId = $context['channel_id'] ?? null;

            if ($senderId && $channelId) {
                $history = AIAgentMessage::query()
                    ->where('channel_id', $channelId)
                    ->where(function ($query) use ($senderId): void {
                        $query->where('direction', 'outbound')
                            ->orWhere('sender_id', $senderId);
                    })
                    ->latest()
                    ->limit($historyTurns * 2)
                    ->get()
                    ->reverse()
                    ->map(fn (AIAgentMessage $msg) => [
                        'role'    => $msg->direction === 'inbound' ? 'user' : 'assistant',
                        'content' => $msg->content,
                    ])
                    ->values()
                    ->all();

                return array_merge($history, [['role' => 'user', 'content' => $userContent]]);
            }
        }

        return [['role' => 'user', 'content' => $userContent]];
    }

    /**
     * Build a multimodal content array from text and attachment data.
     *
     * @param  array<int, array{type: string, mime_type: string, base64: string, filename: string}>  $attachments
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildMultimodalContent(string $text, array $attachments): array
    {
        $parts = [];

        if (! empty($text)) {
            $parts[] = ['type' => 'text', 'text' => $text];
        }

        foreach ($attachments as $attachment) {
            if (str_starts_with($attachment['mime_type'] ?? '', 'image/')) {
                $parts[] = [
                    'type'      => 'image',
                    'mime_type' => $attachment['mime_type'],
                    'base64'    => $attachment['base64'],
                ];
            } else {
                $parts[] = ['type' => 'text', 'text' => '[Attached file: ' . ($attachment['filename'] ?? 'file') . ']'];
            }
        }

        return $parts;
    }

    public function getCategory(): string
    {
        return 'ai';
    }

    public function getLabel(): string
    {
        return 'Ask Agent';
    }

    public function getDescription(): string
    {
        return 'Send a message to an AI model and store the response.';
    }

    public function getIcon(): string
    {
        return 'tabler-brain';
    }

    public function getConfigSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'system_prompt'   => ['type' => 'string', 'description' => 'System prompt for the AI.'],
                'user_message'    => ['type' => 'string', 'description' => 'User message template.'],
                'inject_memory'   => ['type' => 'boolean', 'description' => 'Inject user memory into prompt.'],
                'history_turns'   => ['type' => 'integer', 'description' => 'Number of history turns to include.'],
                'store_output_as' => ['type' => 'string', 'description' => 'Context key to store the response.'],
                'web_search'      => ['type' => 'boolean', 'description' => 'Enable model-native web search.'],
            ],
        ];
    }

    private function interpolate(string $template, array $context): string
    {
        return preg_replace_callback('/\{(\w+)\}/', function (array $matches) use ($context): string {
            $value = $context[$matches[1]] ?? $matches[0];

            if (is_array($value)) {
                return json_encode($value, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) ?: $matches[0];
            }

            return (string) $value;
        }, $template);
    }
}
