<?php

declare(strict_types=1);

namespace TitanAI\Hybrid;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SkillsDiscovered
{
    use Dispatchable, SerializesModels;

    public function __construct(public array $skills, public string $source = 'chatbot') {}
}
