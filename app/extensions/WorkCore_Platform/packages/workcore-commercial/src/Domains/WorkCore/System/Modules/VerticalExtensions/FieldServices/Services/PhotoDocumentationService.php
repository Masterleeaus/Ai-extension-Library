<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Services;

use App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Models\ServiceVisit;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Models\ServiceVisitPhoto;
use Illuminate\Support\Facades\Storage;

class PhotoDocumentationService
{
    /**
     * Add photo to service visit
     */
    public function addPhoto(
        ServiceVisit $serviceVisit,
        string $photoType,
        string $photoPath,
        string $description = '',
        ?float $latitude = null,
        ?float $longitude = null
    ): ServiceVisitPhoto {
        $photoUrl = $this->storePhoto($photoPath);

        return ServiceVisitPhoto::create([
            'service_visit_id' => $serviceVisit->id,
            'photo_type' => $photoType,
            'photo_url' => $photoUrl,
            'description' => $description,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timestamp' => now(),
        ]);
    }

    /**
     * Store photo to storage
     */
    private function storePhoto(string $photoPath): string
    {
        $fileName = 'field-services/' . date('Y/m/d') . '/' . uniqid() . '.jpg';

        if (file_exists($photoPath)) {
            Storage::disk('public')->put($fileName, file_get_contents($photoPath));
        }

        return '/storage/' . $fileName;
    }

    /**
     * Get all photos for a service visit
     */
    public function getPhotosByType(ServiceVisit $serviceVisit, string $photoType): array
    {
        return $serviceVisit->photos()
            ->where('photo_type', $photoType)
            ->get()
            ->toArray();
    }

    /**
     * Verify before/after photos exist
     */
    public function hasBeforeAndAfterPhotos(ServiceVisit $serviceVisit): bool
    {
        $beforeCount = $serviceVisit->photos()
            ->where('photo_type', 'before')
            ->count();

        $afterCount = $serviceVisit->photos()
            ->where('photo_type', 'after')
            ->count();

        return $beforeCount > 0 && $afterCount > 0;
    }

    /**
     * Delete photo
     */
    public function deletePhoto(ServiceVisitPhoto $photo): bool
    {
        if ($photo->photo_url) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $photo->photo_url));
        }

        return $photo->delete();
    }
}
