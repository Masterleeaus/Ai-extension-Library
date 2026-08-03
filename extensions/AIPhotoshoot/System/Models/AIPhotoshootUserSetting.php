<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIPhotoshootUserSetting extends Model
{
    protected $table = 'ai_photo_studio_user_settings';

    protected $fillable = [
        'user_id',
        'num_images',
        'resolution',
        'ratio',
    ];

    protected $casts = [
        'num_images' => 'integer',
    ];

    public const DEFAULT_NUM_IMAGES = 1;

    public const MAX_NUM_IMAGES = 4;

    public const DEFAULT_RESOLUTION = '1K';

    public const DEFAULT_RATIO = '1:1';

    public const RESOLUTIONS = ['1K', '2K', '4K'];

    public const RATIOS = [
        'auto' => 'Auto',
        '21:9' => '21:9 (Cinematic)',
        '16:9' => '16:9 (Widescreen)',
        '3:2'  => '3:2 (Landscape)',
        '4:3'  => '4:3 (Standard Landscape)',
        '1:1'  => '1:1 (Square)',
        '4:5'  => '4:5 (Portrait)',
        '3:4'  => '3:4 (Standard Portrait)',
        '9:16' => '9:16 (Vertical)',
    ];

    /**
     * Map of ratio key to its short descriptor (the parenthesized label
     * from RATIOS), used when rendering ratios in the photoshoot details
     * modal. Keep in sync with RATIOS above.
     *
     * @var array<string, string>
     */
    public const RATIO_DESCRIPTIONS = [
        '21:9' => 'Cinematic',
        '16:9' => 'Widescreen',
        '3:2'  => 'Landscape',
        '4:3'  => 'Standard Landscape',
        '1:1'  => 'Square',
        '4:5'  => 'Portrait',
        '3:4'  => 'Standard Portrait',
        '9:16' => 'Vertical',
    ];

    /**
     * Map of stored ratio/size value to the label shown in the details modal.
     * Mirrors the labels used by the user-facing dropdown so the modal shows
     * the exact option the user picked (e.g. "Square HD" rather than the raw
     * "2048x2048" key). Keep in sync with
     * AIPhotoshootImageModelRegistry::getRatioOptionsForProvider().
     *
     * @var array<string, string>
     */
    public const RATIO_LABELS = [
        '1024x1024' => 'Square',
        '2048x2048' => 'Square HD',
        '1536x1024' => 'Landscape 3:2',
        '2048x1152' => 'Landscape 16:9',
        '3840x2160' => 'Landscape 16:9 4K',
        '1024x1536' => 'Portrait 2:3',
        '2160x3840' => 'Portrait 9:16 4K',
    ];

    /**
     * Resolution to pixel dimensions mapping
     */
    public const RESOLUTION_DIMENSIONS = [
        '1K' => ['width' => 1024, 'height' => 1024],
        '2K' => ['width' => 2048, 'height' => 2048],
        '4K' => ['width' => 4096, 'height' => 4096],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'num_images' => self::DEFAULT_NUM_IMAGES,
                'resolution' => self::DEFAULT_RESOLUTION,
                'ratio'      => self::DEFAULT_RATIO,
            ]
        );
    }

    /**
     * Get the image size based on resolution and ratio.
     *
     * Returns an empty array when the ratio is 'auto' (or otherwise not a
     * valid `W:H` pair) so callers can omit `image_size` from the upstream
     * request and let the provider pick the dimensions.
     */
    public function getImageSize(): array
    {
        return $this->getImageSizeForRatio($this->ratio);
    }

    /**
     * Compute image size for an explicit ratio, using this user's resolution.
     *
     * Returns an empty array when the ratio is 'auto' or otherwise not a
     * valid `W:H` pair so callers can omit `image_size` from the upstream
     * request and let the provider pick the dimensions.
     */
    public function getImageSizeForRatio(?string $ratio): array
    {
        if (! $ratio || $ratio === 'auto') {
            return [];
        }

        $baseDimensions = self::RESOLUTION_DIMENSIONS[$this->resolution] ?? self::RESOLUTION_DIMENSIONS[self::DEFAULT_RESOLUTION];

        $ratioParts = explode(':', $ratio);

        if (count($ratioParts) !== 2) {
            return [];
        }

        $ratioWidth = (int) $ratioParts[0];
        $ratioHeight = (int) $ratioParts[1];

        if ($ratioWidth <= 0 || $ratioHeight <= 0) {
            return [];
        }

        if ($ratioWidth >= $ratioHeight) {
            $width = $baseDimensions['width'];
            $height = (int) round($width * $ratioHeight / $ratioWidth);
        } else {
            $height = $baseDimensions['height'];
            $width = (int) round($height * $ratioWidth / $ratioHeight);
        }

        return ['width' => $width, 'height' => $height];
    }
}
