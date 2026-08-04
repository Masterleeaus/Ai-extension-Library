<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface VoiceEngineContract
{
    public function synthesize(
        string $tenantId,
        string $text,
        array $options = []
    ): string;

    public function transcribe(
        string $tenantId,
        string $audioPath,
        array $options = []
    ): string;

    public function startSession(
        string $tenantId,
        string $conversationId,
        array $config = []
    ): string;

    public function endSession(string $sessionId): bool;

    public function getSessionState(string $sessionId): array;

    public function setVoiceProfile(
        string $tenantId,
        array $config
    ): bool;
}
