<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Domain;

use InvalidArgumentException;

final class Media
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $url,
        public readonly string $mediaType, // image, video, document
        public readonly ?string $productId = null,
        public readonly ?string $altText = null,
        public readonly ?string $fileName = null,
        public readonly ?string $mimeType = null,
        public readonly ?int $fileSize = null,
        public readonly int $sortOrder = 0,
        public readonly bool $isPrimary = false,
        public readonly array $metadata = [],
    ) {
        if (trim($id) === '' || trim($companyId) === '' || trim($url) === '') {
            throw new InvalidArgumentException('Media id, company and URL are required.');
        }
        if (!in_array($mediaType, ['image', 'video', 'document'])) {
            throw new InvalidArgumentException('Invalid media type. Must be image, video, or document.');
        }
    }

    public function isImage(): bool
    {
        return $this->mediaType === 'image';
    }

    public function isVideo(): bool
    {
        return $this->mediaType === 'video';
    }

    public function isDocument(): bool
    {
        return $this->mediaType === 'document';
    }
}
