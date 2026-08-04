<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\TitanAI;

use App\Domains\TitanAI\Contracts\SkillDefinition;
use App\Domains\TitanAI\Registries\UnifiedRegistry;
use LogicException;
use PHPUnit\Framework\TestCase;

final class UnifiedRegistryTest extends TestCase
{
    public function test_it_registers_and_summarises_a_skill(): void
    {
        $registry = new UnifiedRegistry();
        $skill = new class implements SkillDefinition {
            public function key(): string { return 'test-skill'; }
            public function name(): string { return 'Test Skill'; }
            public function description(): string { return 'Test'; }
            public function metadata(): array { return ['source' => 'test']; }
            public function canHandle(string $intent): bool { return $intent === 'test'; }
            public function handle(string $intent, array $context = []): string { return 'handled'; }
            public function trainingExamples(): array { return []; }
        };

        $registry->registerSkill($skill->key(), $skill);

        self::assertSame($skill, $registry->getSkill('test-skill'));
        self::assertSame(1, $registry->counts()['skills']);
        self::assertSame(['test-skill'], $registry->summary()['skills']);

        $snapshot = $registry->allSkills();
        $snapshot->put('injected', $skill);
        self::assertFalse($registry->hasSkill('injected'));
    }

    public function test_it_rejects_duplicate_keys_by_default(): void
    {
        $registry = new UnifiedRegistry();
        $skill = new class implements SkillDefinition {
            public function key(): string { return 'duplicate'; }
            public function name(): string { return 'Duplicate'; }
            public function description(): string { return ''; }
            public function metadata(): array { return []; }
            public function canHandle(string $intent): bool { return false; }
            public function handle(string $intent, array $context = []): string { return ''; }
            public function trainingExamples(): array { return []; }
        };

        $registry->registerSkill($skill->key(), $skill);

        $this->expectException(LogicException::class);
        $registry->registerSkill($skill->key(), $skill);
    }
}
