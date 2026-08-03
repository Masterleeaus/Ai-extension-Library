<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier0\ModelRouting\Providers;

final class NanoBananaProviderAdapter
{
    public function id(): string { return 'nano-banana'; }
    public function capability(): string { return 'image-generation-and-editing'; }
    public function runtime(): string { return 'workcore-native-ai-runtime'; }
    public function requiresCompanyCredential(): bool { return true; }
}
