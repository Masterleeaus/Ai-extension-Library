<?php

declare(strict_types=1);

namespace App\Extensions\AIAgentWhatsappChannel\System\Media\Exceptions;

use Exception;

final class MediaNotFoundException extends Exception
{
    public static function byAttachmentId(string $attachmentId): self
    {
        return new self("Media attachment '{$attachmentId}' not found or expired");
    }

    public static function expired(string $attachmentId): self
    {
        return new self("Media attachment '{$attachmentId}' has expired and been cleaned up");
    }
}
