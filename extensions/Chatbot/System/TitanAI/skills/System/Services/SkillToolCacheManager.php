<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Services;

use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use Closure;
use Illuminate\Support\Facades\Cache;

final class SkillToolCacheManager
{
    private const PREFIX = 'skill_tools';
    private const DEFAULT_TTL = 3600;
    private const GENERATION_KEY = self::PREFIX . ':generation';

    public function remember(string $key, Closure $callback, ?int $ttl = null): mixed
    {
        return Cache::remember($this->key($key), $ttl ?? self::DEFAULT_TTL, $callback);
    }

    public function forget(string $key): bool
    {
        return Cache::forget($this->key($key));
    }

    public function invalidateAll(): int
    {
        $generation = $this->generation() + 1;
        Cache::forever(self::GENERATION_KEY, $generation);
        return $generation;
    }

    public function invalidateSkill(Skill|int $skill): int
    {
        return $this->invalidateAll();
    }

    public function warm(Skill $skill, Closure $callback): mixed
    {
        return $this->remember('warm:skill:' . $skill->getKey(), $callback, 7200);
    }

    /** @return array<string, mixed> */
    public function stats(): array
    {
        return [
            'prefix' => self::PREFIX,
            'generation' => $this->generation(),
            'default_ttl' => self::DEFAULT_TTL,
            'driver' => config('cache.default'),
        ];
    }

    private function key(string $key): string
    {
        return self::PREFIX . ':g' . $this->generation() . ':' . hash('sha256', $key);
    }

    private function generation(): int
    {
        return max(1, (int) Cache::get(self::GENERATION_KEY, 1));
    }
}
