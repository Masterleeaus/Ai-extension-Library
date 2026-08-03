<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime\Skills;

use App\Domains\TitanAI\Contracts\SkillDefinition;
use InvalidArgumentException;
use LogicException;

/**
 * Exposes a signed bundled Chatbot skill through the shared TitanAI contract.
 */
final class UnifiedSkillAdapter implements SkillDefinition
{
    /** @param array<string, mixed> $definition */
    public function __construct(private readonly array $definition)
    {
        if (empty($definition['slug'])) {
            throw new InvalidArgumentException('Bundled skill definition requires a slug.');
        }
    }

    public function key(): string
    {
        return (string) $this->definition['slug'];
    }

    public function name(): string
    {
        return (string) ($this->definition['name'] ?? ucwords(str_replace('-', ' ', $this->key())));
    }

    public function description(): string
    {
        return (string) ($this->definition['description'] ?? '');
    }

    public function metadata(): array
    {
        return [
            'source' => 'chatbot',
            'version' => (string) ($this->definition['version'] ?? '1.0.0'),
            'capabilities' => array_values((array) ($this->definition['capabilities'] ?? [])),
            'tools' => array_values((array) ($this->definition['tools'] ?? [])),
            'sha256' => $this->definition['sha256'] ?? null,
            'integrity_verified' => isset($this->definition['sha256']),
            'execution_mode' => 'governed_context',
        ];
    }

    public function canHandle(string $intent): bool
    {
        $intent = strtolower($intent);
        foreach ($this->searchTerms() as $term) {
            if (strlen($term) >= 4 && str_contains($intent, $term)) {
                return true;
            }
        }
        return false;
    }

    public function handle(string $intent, array $context = []): string
    {
        throw new LogicException(
            'Bundled Chatbot skills execute through FieldServiceSkillContextProvider and cannot be invoked directly.',
        );
    }

    public function trainingExamples(): array
    {
        return array_values((array) ($this->definition['examples'] ?? []));
    }

    /** @return list<string> */
    private function searchTerms(): array
    {
        $terms = [
            ...explode('-', $this->key()),
            ...(array) ($this->definition['capabilities'] ?? []),
            ...(array) ($this->definition['tools'] ?? []),
        ];
        $tokens = [];
        foreach ($terms as $term) {
            foreach (preg_split('/[^a-z0-9]+/i', strtolower((string) $term)) ?: [] as $token) {
                if ($token !== '') {
                    $tokens[] = $token;
                }
            }
        }
        return array_values(array_unique($tokens));
    }
}
