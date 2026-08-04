<?php

declare(strict_types=1);

namespace App\Extensions\AIAgentWhatsappChannel\System\Media\Exceptions;

use Exception;

final class MediaAccessDeniedException extends Exception
{
    public static function crossTenantAccess(int $requestingTenant, int $mediaOwner): self
    {
        return new self(
            "Tenant {$requestingTenant} cannot access media owned by tenant {$mediaOwner}",
        );
    }
}
