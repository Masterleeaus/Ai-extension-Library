<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime\Skills;

use RuntimeException;

final class FieldServiceSkillRegistry
{
    /** @var array<string,array<string,mixed>>|null */
    private ?array $skills = null;

    /** @return array<string,array<string,mixed>> */
    public function all(): array
    {
        if ($this->skills !== null) return $this->skills;

        $root = dirname(__DIR__, 2).'/skills';
        $indexPath = $root.'/bundled-skills.json';
        if (! is_file($indexPath)) return $this->skills = [];

        $index = json_decode((string) file_get_contents($indexPath), true, flags: JSON_THROW_ON_ERROR);
        $loaded = [];
        foreach ((array) ($index['skills'] ?? []) as $entry) {
            if (! is_array($entry) || empty($entry['slug']) || empty($entry['path'])) continue;
            $path = $root.'/'.ltrim((string) $entry['path'], '/');
            if (! is_file($path)) continue;
            $content = (string) file_get_contents($path);
            $actualHash = hash('sha256', $content);
            if (! hash_equals((string) ($entry['sha256'] ?? ''), $actualHash)) {
                throw new RuntimeException('Bundled skill integrity check failed for ['.$entry['slug'].'].');
            }
            $loaded[(string) $entry['slug']] = [
                'slug' => (string) $entry['slug'],
                'version' => (string) ($entry['version'] ?? '1.0.0'),
                'description' => $this->frontmatterValue($content, 'description'),
                'capabilities' => $this->inlineList($content, 'capabilities'),
                'tools' => $this->toolKeys($content),
                'instructions' => $this->body($content),
                'sha256' => $actualHash,
            ];
        }
        return $this->skills = $loaded;
    }

    /** @return list<array<string,mixed>> */
    public function match(string $message, ?string $template = null, int $limit = 3): array
    {
        $haystack = strtolower($message.' '.($template ?? ''));
        $scores = [];
        foreach ($this->all() as $slug => $skill) {
            $terms = array_unique(array_filter([
                ...explode('-', $slug),
                ...(array) ($skill['capabilities'] ?? []),
                ...(array) ($skill['tools'] ?? []),
            ]));
            $score = 0;
            foreach ($terms as $term) {
                $term = strtolower(str_replace(['.', '_'], ' ', (string) $term));
                foreach (preg_split('/\s+/', $term) ?: [] as $token) {
                    if (strlen($token) >= 4 && str_contains($haystack, $token)) $score++;
                }
            }
            if ($score > 0) $scores[$slug] = $score;
        }
        arsort($scores);
        $result = [];
        foreach (array_slice(array_keys($scores), 0, max(1, $limit)) as $slug) $result[] = $this->all()[$slug];
        return $result;
    }

    private function frontmatterValue(string $content, string $key): ?string
    {
        if (preg_match('/^'.preg_quote($key, '/').':\s*["\']?(.*?)["\']?\s*$/m', $content, $m) !== 1) return null;
        return trim($m[1], " \t\n\r\0\x0B\"'");
    }

    /** @return list<string> */
    private function inlineList(string $content, string $key): array
    {
        if (preg_match('/^'.preg_quote($key, '/').':\s*\[(.*?)\]\s*$/m', $content, $m) !== 1) return [];
        return array_values(array_filter(array_map(static fn (string $v): string => trim($v, " \t\"'"), explode(',', $m[1]))));
    }

    /** @return list<string> */
    private function toolKeys(string $content): array
    {
        preg_match_all('/\bkey:\s*([a-z0-9._-]+)/i', $content, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }

    private function body(string $content): string
    {
        if (preg_match('/\A---\R.*?\R---\R(.*)\z/s', $content, $m) === 1) return trim($m[1]);
        return trim($content);
    }
}
