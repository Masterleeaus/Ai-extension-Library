<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tenancy;

final readonly class CompanyContext
{
    public function __construct(
        public ?int $companyId,
        public ?string $role,
        public bool $isManager,
    ) {}

    public static function none(): self
    {
        return new self(null, null, false);
    }
}
