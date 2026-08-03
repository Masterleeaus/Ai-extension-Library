<?php

namespace TitanAI\Hybrid\Contracts;

interface SkillDefinition extends Registrable
{
    public function canHandle(string $intent): bool;
    public function handle(string $intent, array $context = []): string;
    public function trainingExamples(): array;
}
