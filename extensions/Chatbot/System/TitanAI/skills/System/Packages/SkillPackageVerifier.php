<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Packages;

final readonly class SkillPackageVerifier
{
    public function __construct(private SkillPackageHasher $hasher)
    {
    }

    /**
     * @param array<string, mixed> $manifest
     * @param list<SkillResourceFingerprint> $resources
     */
    public function verify(
        SkillPackageFingerprint $expected,
        array $manifest,
        string $instructions,
        array $resources = [],
    ): SkillPackageVerificationResult {
        $actual = $this->hasher->fingerprint($manifest, $instructions, $resources);
        $differences = [];

        if (! hash_equals($expected->manifestHash, $actual->manifestHash)) {
            $differences[] = 'manifest';
        }
        if (! hash_equals($expected->instructionsHash, $actual->instructionsHash)) {
            $differences[] = 'instructions';
        }
        if ($expected->resourceHashes !== $actual->resourceHashes) {
            $differences[] = 'resources';
        }
        if (! hash_equals($expected->packageHash, $actual->packageHash) && $differences === []) {
            $differences[] = 'package_identity';
        }

        if ($differences === [] && hash_equals($expected->packageHash, $actual->packageHash)) {
            return new SkillPackageVerificationResult(SkillPackageVerificationStatus::VERIFIED, $actual);
        }

        $status = in_array('resources', $differences, true)
            ? SkillPackageVerificationStatus::CORRUPTED
            : SkillPackageVerificationStatus::MODIFIED;

        return new SkillPackageVerificationResult(
            status: $status,
            actual: $actual,
            differences: $differences,
            message: $status === SkillPackageVerificationStatus::CORRUPTED
                ? 'One or more bundled resources differ from the recorded release.'
                : 'The skill manifest or instructions differ from the recorded release.',
        );
    }
}
