<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatMemory\System\Enums;

enum MemoryType: string
{
    case Episodic = 'episodic';
    case Semantic = 'semantic';
    case Procedural = 'procedural';
    case Preference = 'preference';
    case Relationship = 'relationship';
    case Operational = 'operational';
}
