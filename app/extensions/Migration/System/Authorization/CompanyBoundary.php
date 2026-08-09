<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Authorization;

final class CompanyBoundary
{
    public static function allows(?int $actorCompanyId, ?int $resourceCompanyId): bool
    {
        return $actorCompanyId !== null
            && $resourceCompanyId !== null
            && $actorCompanyId === $resourceCompanyId;
    }
}
