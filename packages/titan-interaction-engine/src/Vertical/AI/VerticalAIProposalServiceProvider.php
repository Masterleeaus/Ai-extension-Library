<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Vertical\AI;

use Illuminate\Support\ServiceProvider;
use TitanZero\Interaction\AI\AIServiceInterface;

final class VerticalAIProposalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VerticalAIProposalValidator::class);
        $this->app->singleton(VerticalAIProposalSanitizer::class);
        $this->app->singleton(DeterministicVerticalAIProposalFallback::class);
        $this->app->singleton(VerticalAIProposalBridge::class, function ($app): VerticalAIProposalBridge {
            return new VerticalAIProposalBridge(
                ai: $app->make(AIServiceInterface::class),
                validator: $app->make(VerticalAIProposalValidator::class),
                sanitizer: $app->make(VerticalAIProposalSanitizer::class),
                fallback: $app->make(DeterministicVerticalAIProposalFallback::class),
                providerId: (string) config('interaction.ai.provider', 'configured'),
                modelId: (string) config('interaction.ai.model', 'configured'),
                maxAttempts: (int) config('interaction.ai.proposal.max_attempts', 2),
            );
        });
    }
}
