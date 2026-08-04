<?php declare(strict_types=1);
namespace App\Extensions\Chatbot\System\Integration\WorkCore;

final class PropertyOperationsQueryService extends BaseWorkCoreService
{
    public function getPropertyInfo(string $propertyId): string
    {
        if (!$this->authorize('read', 'property')) return "Can't access property info.";

        $property = $this->queryProperty($propertyId, $this->getTenantId());
        if (!$property) return "Property not found.";

        return "📍 **{$property['address']}**\nType: {$property['type']}\nStatus: {$property['status']}";
    }

    public function listPropertyDocuments(string $propertyId): string
    {
        if (!$this->authorize('read', 'document')) return "Can't access documents.";

        $docs = $this->queryDocuments($propertyId);
        if ($docs->isEmpty()) return "No documents on file.";

        $list = $docs->map(fn($d) => "📄 {$d['filename']}")->implode("\n");
        return "Documents:\n$list";
    }

    private function queryProperty(string $propertyId, int $tenantId): ?array { return null; }
    private function queryDocuments(string $propertyId) { return collect([]); }
}
