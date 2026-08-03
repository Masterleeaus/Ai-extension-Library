<?php

namespace App\Domains\TitanAI\Contracts;

/**
 * SkillDefinition Contract
 * 
 * Defines skills/capabilities that the Chatbot can use.
 */
interface SkillDefinition extends Registrable
{
    /**
     * Determine if this skill can handle the user intent.
     * 
     * @param string $intent
     * @return bool
     */
    public function canHandle(string $intent): bool;

    /**
     * Handle the intent and return response.
     * 
     * @param string $intent
     * @param array $context [optional]
     * @return string
     * @throws \Exception
     */
    public function handle(string $intent, array $context = []): string;

    /**
     * Get skill training data (examples).
     * 
     * @return array
     */
    public function trainingExamples(): array;
}
