<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools;

use App\Services\AI\Tier4\Tools\Contracts\AbstractTool;

final class DocumentSignatureTool extends AbstractTool
{
    public static function id(): string { return 'document-signature'; }
    public static function name(): string { return 'Document Signature Tool'; }
    public static function operation(): string { return 'Request a document signature'; }
    public static function requiresApproval(): bool { return true; }
    public static function permissions(): array { return ['documents.signatures.create']; }
}
