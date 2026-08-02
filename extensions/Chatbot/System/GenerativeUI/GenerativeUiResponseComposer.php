<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\GenerativeUI;

final class GenerativeUiResponseComposer
{
    public function __construct(
        private readonly BuilderRegistry $registry,
        private readonly GenerativeUiSpecNormaliser $normaliser,
        private readonly GenerativeUiSpecValidator $validator,
    ) {
    }

    public function shouldGenerate(string $prompt): bool
    {
        if (! (bool) config('chatbot.generative_ui.enabled', true)) {
            return false;
        }

        $trimmed = trim($prompt);
        if (preg_match('/^\/(?:ui|interface|canvas)\b/i', $trimmed)) {
            return true;
        }

        if (! (bool) config('chatbot.generative_ui.natural_language_triggers', true)) {
            return false;
        }

        return (bool) preg_match('/\b(?:show|build|create|design|render|generate|display|make)\b.{0,80}\b(?:dashboard|form|interface|ui|card|table|calendar|schedule|quote|invoice|portal|kanban|chart|report|workspace|preview)\b/i', $trimmed);
    }

    public function augmentPrompt(string $prompt, string $surface = 'chat'): string
    {
        $components = implode(', ', $this->registry->componentIds());
        $actions = implode(', ', $this->registry->actionIds());
        $max = (int) config('chatbot.generative_ui.preferred_chat_elements', 50);

        return <<<PROMPT
[SYSTEM: TITAN ZERO PRESENTATION-ONLY UI COMPOSER]
The user explicitly requested a generated interface. Return one concise explanation followed by exactly one fenced `titan-ui` JSON block.
Use this flat structure: {"version":"1.1","authority":"presentation-only","surface":"{$surface}","root":"root","state":{},"meta":{"title":"Generated interface"},"persistence":{"scope":"message"},"elements":{"root":{"type":"stack","props":{},"children":[]}}}.
Allowed components: {$components}.
Allowed action intents: {$actions}.
Use fewer than {$max} elements for chat. Every child must reference an existing element. Prefer compact summaries with progressive disclosure and canvas expansion for complex layouts. Use state, read-only data, visibility, repeats and accessible labels where useful. Never output HTML, Blade, JavaScript, SQL, credentials or claims that an action executed. Actions are preview intents only. Use AUD for generic currency examples.
[END SYSTEM]

USER REQUEST:
{$prompt}
PROMPT;
    }

    /** @return array{accepted: bool, fallback_text: string, spec: ?array, changes: array, issues: array} */
    public function compose(string $response, string $surface = 'chat'): array
    {
        $fallback = trim((string) preg_replace('/```titan-ui[\s\S]*?```/i', '', $response));
        if (! preg_match('/```titan-ui\s*([\s\S]*?)```/i', $response, $matches)) {
            return ['accepted' => false, 'fallback_text' => $fallback ?: trim($response), 'spec' => null, 'changes' => [], 'issues' => [['path' => '/', 'message' => 'No titan-ui block was returned.']]];
        }

        $decoded = json_decode(trim($matches[1]), true);
        if (! is_array($decoded)) {
            return ['accepted' => false, 'fallback_text' => $fallback ?: 'The generated interface could not be decoded.', 'spec' => null, 'changes' => [], 'issues' => [['path' => '/', 'message' => 'The titan-ui block is not valid JSON.']]];
        }

        $repair = $this->normaliser->repair($decoded, $surface);
        $validation = $this->validator->validate($repair['spec']);
        if (! $validation['valid']) {
            return ['accepted' => false, 'fallback_text' => $fallback ?: 'The generated interface was rejected by the safety validator.', 'spec' => null, 'changes' => $repair['changes'], 'issues' => $validation['issues']];
        }

        if ($repair['changes'] !== []) {
            $fallback = trim(($fallback ?: 'Here is the generated interface.').' Minor presentation-schema drift was repaired before rendering.');
        }

        return ['accepted' => true, 'fallback_text' => $fallback ?: 'Here is the generated interface.', 'spec' => $validation['spec'], 'changes' => $repair['changes'], 'issues' => []];
    }

    public function encodeEnvelope(string $fallbackText, array $spec): string
    {
        return (string) json_encode([
            'type' => 'titan-ui',
            'version' => '1.1',
            'authority' => 'presentation-only',
            'fallback_text' => $fallbackText,
            'spec' => $spec,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** @return array{fallback_text: string, spec: array, meta: array}|null */
    public function decodeEnvelope(?string $payload): ?array
    {
        if (! is_string($payload) || $payload === '') {
            return null;
        }
        $decoded = json_decode($payload, true);
        if (! is_array($decoded) || ($decoded['type'] ?? null) !== 'titan-ui' || ! is_array($decoded['spec'] ?? null)) {
            return null;
        }
        $validation = $this->validator->validate($decoded['spec']);
        if (! $validation['valid']) {
            return null;
        }
        return [
            'fallback_text' => is_string($decoded['fallback_text'] ?? null) ? $decoded['fallback_text'] : 'Generated interface',
            'spec' => $validation['spec'],
            'meta' => is_array($validation['spec']['meta'] ?? null) ? $validation['spec']['meta'] : [],
        ];
    }
}
