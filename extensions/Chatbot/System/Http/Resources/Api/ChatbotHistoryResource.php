<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Resources\Api;

use App\Extensions\Chatbot\System\GenerativeUI\GenerativeUiResponseComposer;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class ChatbotHistoryResource extends JsonResource
{
    public function toArray(Request $request): array|Arrayable|JsonSerializable
    {
        $envelope = null;
        if ($this->getAttribute('message_type') === 'generative_ui' || $this->getAttribute('content_type') === 'titan-ui') {
            $envelope = app(GenerativeUiResponseComposer::class)->decodeEnvelope($this->getAttribute('message'));
        }

        return [
            'id'                  => $this->getAttribute('id'),
            'message_id'          => $this->getAttribute('message_id'),
            'conversation_id'     => $this->getAttribute('conversation_id'),
            'role'                => $this->getAttribute('role'),
            'user_id'             => $this->getAttribute('user_id'),
            'ip_address'          => $this->getAttribute('ip_address'),
            'message'             => $envelope['fallback_text'] ?? $this->getAttribute('message'),
            'message_type'        => $this->getAttribute('message_type'),
            'content_type'        => $this->getAttribute('content_type'),
            'is_generative_ui'    => $envelope !== null,
            'fallback_text'       => $envelope['fallback_text'] ?? null,
            'ui_spec'             => $envelope['spec'] ?? null,
            'ui_meta'             => $envelope['meta'] ?? null,
            'is_internal_note'    => $this->getAttribute('is_internal_note'),
            'media_url'           => $this->getAttribute('media_url'),
            'media_name'          => $this->getAttribute('media_name'),
            'user'                => $this->getAttribute('user'),
            'created_at'          => $this->getAttribute('created_at')?->timezone($this->timezone()),
            'read_at'          	  => $this->getAttribute('read_at'),
            'isHTML'              => $envelope === null && $this->isHTML($this->getAttribute('message')),
        ];
    }

    public function timezone(): array|string
    {
        $timezone = request()?->header('x-timezone');

        if (is_string($timezone)) {
            return $timezone;
        }

        return 'UTC';
    }

    private function isHTML(?string $content): bool
    {
        return str_contains($content ?? '', 'lqd-ext-chatbot-html-response');
    }
}
