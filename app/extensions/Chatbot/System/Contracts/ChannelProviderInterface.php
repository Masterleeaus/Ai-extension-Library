<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Contracts;

interface ChannelProviderInterface
{
    public function key(): string;

    /** @return list<string> */
    public function capabilities(): array;

    /** @param array<string,mixed> $message @return array<string,mixed> */
    public function send(array $message): array;

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    public function normalizeInbound(array $payload): array;

    /** @param array<string,string|string[]> $headers */
    public function verifyWebhook(array $headers, string $body): bool;
}
