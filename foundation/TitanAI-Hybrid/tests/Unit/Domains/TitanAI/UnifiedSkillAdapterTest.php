<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\TitanAI;

use App\Extensions\Chatbot\System\TitanAI\Runtime\Skills\UnifiedSkillAdapter;
use LogicException;
use PHPUnit\Framework\TestCase;

final class UnifiedSkillAdapterTest extends TestCase
{
    public function test_it_preserves_metadata_without_exposing_internal_instructions(): void
    {
        $skill = new UnifiedSkillAdapter([
            'slug' => 'deep-clean-planner',
            'version' => '1.0.0',
            'description' => 'Plans deep cleaning work.',
            'capabilities' => ['deep-clean'],
            'tools' => ['jobs.read'],
            'instructions' => 'Follow the site-safe planning procedure.',
            'sha256' => str_repeat('a', 64),
        ]);

        self::assertSame('deep-clean-planner', $skill->key());
        self::assertTrue($skill->canHandle('Create a deep clean plan'));
        self::assertTrue($skill->metadata()['integrity_verified']);
        self::assertSame('governed_context', $skill->metadata()['execution_mode']);

        $this->expectException(LogicException::class);
        $skill->handle('plan');
    }
}
