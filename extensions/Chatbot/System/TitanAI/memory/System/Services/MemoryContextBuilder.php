<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatMemory\System\Services;

final class MemoryContextBuilder
{
    public function build(iterable $memories): array
    {
        $context = [];
        foreach ($memories as $memory) {
            $context[] = [
                'type' => $memory->type,
                'scope' => [$memory->scope_type, $memory->scope_id],
                'content' => $memory->content,
                'confidence' => $memory->confidence,
                'importance' => $memory->importance,
                'source' => $memory->source,
            ];
        }
        return $context;
    }
}
