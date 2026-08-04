<?php declare(strict_types=1);
namespace App\Extensions\AIChatPro\System\Integration\WorkCore;

/**
 * WorkCore PropertyOperations Integration for AiChatPro
 * Issue #191: PropertyOperations → AiChatPro Properties
 */
final class PropertyOperationsQueryService extends BaseWorkCoreService
{
    public function listProperties(int $limit = 100): array
    {
        return $this->authorize('read', 'property') ? $this->list('property', $limit) : [];
    }

    public function getProperty(string $propertyId): ?array
    {
        return $this->authorize('read', 'property') ? null : null;
    }

    public function listAssets(string $propertyId): array
    {
        return $this->authorize('read', 'asset') ? [] : [];
    }

    public function getDocuments(string $propertyId): array
    {
        return $this->authorize('read', 'document') ? [] : [];
    }
}
