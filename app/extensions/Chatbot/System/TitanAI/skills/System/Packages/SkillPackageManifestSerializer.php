<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Packages;

use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use App\Extensions\SystemAIChatSkills\System\Models\SkillVersion;

final readonly class SkillPackageManifestSerializer
{
    public function __construct(private SkillPackageHasher $hasher)
    {
    }

    public function serialize(Skill $skill, SkillVersion $version): string
    {
        $resources = [];
        foreach ($version->resources()->orderBy('path')->get() as $resource) {
            $resources[] = [
                'path' => $resource->path,
                'sha256' => $resource->sha256,
                'size' => (int) $resource->size,
                'mime_type' => $resource->mime_type,
            ];
        }

        $manifest = [
            'contract' => 'system-ai-chat-skill-package/1',
            'skill' => [
                'key' => $skill->slug,
                'name' => $version->name,
                'version' => $version->version,
            ],
            'identity' => [
                'package_hash' => $version->package_hash ?: $version->content_hash,
                'manifest_hash' => $version->manifest_hash,
                'instructions_hash' => $version->instructions_hash,
            ],
            'resources' => $resources,
            'provenance' => [
                'source' => $version->source,
                'source_url' => $version->source_url,
                'repository' => $version->source_repository,
                'branch' => $version->source_branch,
                'commit_sha' => $version->source_commit_sha,
                'imported_at' => $version->imported_at?->toAtomString(),
            ],
            'verification' => [
                'status' => $version->verification_status,
                'verified_at' => $version->verified_at?->toAtomString(),
            ],
        ];

        return $this->hasher->canonicalJson($manifest) . "\n";
    }
}
