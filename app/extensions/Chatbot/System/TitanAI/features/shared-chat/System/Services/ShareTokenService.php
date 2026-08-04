<?php

declare(strict_types=1);

namespace App\Extensions\SharedChat\System\Services;

final class ShareTokenService
{
    public function __construct(private readonly string $secret) {}

    public function generate(int $categoryId, int $chatId, int $messageId, int $issuedAt): string
    {
        return hash_hmac('sha256', $this->payload($categoryId, $chatId, $messageId, $issuedAt), $this->secret);
    }

    public function verify(string $token, int $categoryId, int $chatId, int $messageId, int $issuedAt): bool
    {
        return hash_equals($this->generate($categoryId, $chatId, $messageId, $issuedAt), $token);
    }

    private function payload(int $categoryId, int $chatId, int $messageId, int $issuedAt): string
    {
        return implode(':', [$categoryId, $chatId, $messageId, $issuedAt]);
    }
}
