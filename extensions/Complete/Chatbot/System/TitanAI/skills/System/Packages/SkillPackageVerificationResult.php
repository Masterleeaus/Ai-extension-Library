<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Packages;

final readonly class SkillPackageVerificationResult
{
    /** @param list<string> $differences */
    public function __construct(
        public string $status,
        public SkillPackageFingerprint $actual,
        public array $differences = [],
        public ?string $message = null,
    ) {
    }

    public function isVerified(): bool
    {
        return $this->status === SkillPackageVerificationStatus::VERIFIED;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'differences' => $this->differences,
            'message' => $this->message,
            'actual' => $this->actual->toArray(),
        ];
    }
}
