<?php

declare(strict_types=1);

namespace TitanZero\Interaction\AI;

final class NullAIService implements AIServiceInterface
{
    public function generate(string $prompt, array $options = []): string
    {
        throw new \RuntimeException('External AI is not part of this offline build. LocalBrain v2 remains available.');
    }
}
