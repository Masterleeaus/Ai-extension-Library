<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\database\seeders;

use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use Illuminate\Database\Seeder;
use RuntimeException;

final class FieldServiceSkillsSeeder extends Seeder
{
    /** @var list<string> */
    private const SLUGS = [
        'deep-clean-planner',
        'carpet-cleaning-specialist',
        'eco-friendly-cleaning-advisor',
        'handyman-quote-generator',
        'maintenance-scheduler',
        'field-service-dispatcher',
        'equipment-maintenance-tracker',
    ];

    public function run(): void
    {
        foreach (self::SLUGS as $slug) {
            $path = dirname(__DIR__, 2) . '/bundled/' . $slug . '/SKILL.md';
            if (! is_file($path)) {
                throw new RuntimeException("Bundled skill file missing: {$path}");
            }

            $parsed = Skill::parseSkillMd((string) file_get_contents($path));
            Skill::query()->updateOrCreate(
                ['slug' => $slug, 'scope_key' => 'platform:0'],
                [
                    'user_id' => null,
                    'company_id' => null,
                    'ownership_type' => 'platform',
                    'visibility' => 'platform_public',
                    'scope_key' => 'platform:0',
                    'name' => (string) ($parsed['name'] ?: $slug),
                    'description' => (string) $parsed['description'],
                    'instructions' => (string) $parsed['instructions'],
                    'bundled_resources' => null,
                    'source' => 'seeded',
                    'is_public' => true,
                    'status' => 'active',
                    'version' => (string) $parsed['version'],
                    'verification_status' => 'verified',
                    'metadata' => $parsed['metadata'],
                ],
            );
        }
    }
}
