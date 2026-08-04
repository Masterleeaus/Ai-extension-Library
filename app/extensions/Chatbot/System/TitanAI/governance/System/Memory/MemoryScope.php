<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Memory;

enum MemoryScope: string
{
    case Conversation = 'conversation';
    case Session = 'session';
    case User = 'user';
    case Business = 'business';
    case Domain = 'domain';
    case Agent = 'agent';
    case Episodic = 'episodic';
    case Semantic = 'semantic';
    case Procedural = 'procedural';
    case Reflection = 'reflection';
}
