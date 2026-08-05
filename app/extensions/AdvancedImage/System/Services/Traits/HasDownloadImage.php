<?php

namespace App\Extensions\AdvancedImage\System\Services\Traits;

use App\Services\Security\RemoteImageFetcher;
use Throwable;

trait HasDownloadImage
{
    public function downloadAndSaveImageFromUrl($url): ?string
    {
        try {
            $path = app(RemoteImageFetcher::class)->store(
                (string) $url,
                'uploads',
                'image-editor'
            );

            return $this->imagePath($path);
        } catch (Throwable) {
            return null;
        }
    }
}
