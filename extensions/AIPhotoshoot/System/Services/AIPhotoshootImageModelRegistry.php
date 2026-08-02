<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Services;

use App\Domains\Entity\Enums\EntityEnum;

/**
 * Registry of supported image generation models for AI Photoshoot.
 *
 * Mirrors FashionStudio's registry. Each entry pairs an EntityEnum-backed
 * model with the provider that handles it and metadata used by both the
 * admin UI and the runtime dispatcher in BaseAIPhotoshootController. To
 * add a new model/provider, append an entry to the MODELS map below — the
 * controller will route it automatically based on the `provider` field.
 *
 * @phpstan-type ModelMeta array{label: string, provider: 'fal'|'openai', supports_edit: bool, admin_selectable: bool}
 */
class AIPhotoshootImageModelRegistry
{
    public const PROVIDER_FAL = 'fal';

    public const PROVIDER_OPENAI = 'openai';

    public const SETTING_KEY = 'ai-photoshoot-image-default-model';

    public const SETTING_OPENAI_QUALITY = 'ai-photoshoot-openai-quality';

    public const SETTING_OPENAI_SIZE = 'ai-photoshoot-openai-size';

    public const SETTING_FAL_RATIO = 'ai-photoshoot-fal-ratio';

    public const SETTING_FAL_RESOLUTION = 'ai-photoshoot-fal-resolution';

    /**
     * Allowed nano-banana / FAL aspect ratios per the nano-banana-pro/edit API.
     *
     * @var array<int, string>
     */
    public const FAL_RATIO_VALUES = [
        'auto',
        '1:1',
        '16:9',
        '9:16',
        '4:3',
        '3:4',
        '3:2',
        '2:3',
        '5:4',
        '4:5',
        '21:9',
    ];

    /**
     * Allowed nano-banana / FAL resolutions per the nano-banana-pro/edit API.
     *
     * @var array<int, string>
     */
    public const FAL_RESOLUTION_VALUES = ['1K', '2K', '4K'];

    public const FAL_RATIO_DEFAULT = 'auto';

    public const FAL_RESOLUTION_DEFAULT = '1K';

    public const SETTING_MAX_UPLOAD_SIZE_MB = 'ai-photoshoot-max-upload-size-mb';

    public const DEFAULT_MAX_UPLOAD_SIZE_MB = 5;

    public static function getMaxUploadSizeMb(): int
    {
        $value = (int) setting(self::SETTING_MAX_UPLOAD_SIZE_MB, self::DEFAULT_MAX_UPLOAD_SIZE_MB);

        return $value > 0 ? $value : self::DEFAULT_MAX_UPLOAD_SIZE_MB;
    }

    public static function getMaxUploadSizeKb(): int
    {
        return self::getMaxUploadSizeMb() * 1024;
    }

    /**
     * Allowed OpenAI image quality values for gpt-image-* models.
     *
     * @var array<int, string>
     */
    public const OPENAI_QUALITY_VALUES = ['low', 'medium', 'high', 'auto'];

    /**
     * Allowed OpenAI image size values, per the official image-generation
     * guide (https://developers.openai.com/api/docs/guides/image-generation).
     * All gpt-image-* models accept these "popular sizes".
     *
     * @var array<int, string>
     */
    public const OPENAI_SIZE_VALUES = [
        '1024x1024',
        '1024x1536',
        '1536x1024',
        '2048x2048',
        '2048x1152',
        '3840x2160',
        '2160x3840',
        'auto',
    ];

    public const OPENAI_QUALITY_DEFAULT = 'low';

    public const OPENAI_SIZE_DEFAULT = '1024x1024';

    /**
     * Map of EntityEnum value => provider metadata.
     *
     * @return array<string, ModelMeta>
     */
    public static function models(): array
    {
        return [
            EntityEnum::NANO_BANANA_PRO->value => [
                'label'            => 'Nano Banana Pro',
                'provider'         => self::PROVIDER_FAL,
                'supports_edit'    => true,
                'admin_selectable' => true,
            ],
            EntityEnum::GPT_IMAGE_1->value => [
                'label'            => 'GPT Image 1',
                'provider'         => self::PROVIDER_OPENAI,
                'supports_edit'    => true,
                'admin_selectable' => true,
            ],
            EntityEnum::GPT_IMAGE_1_5->value => [
                'label'            => 'GPT Image 1.5',
                'provider'         => self::PROVIDER_OPENAI,
                'supports_edit'    => true,
                'admin_selectable' => true,
            ],
            EntityEnum::GPT_IMAGE_2->value => [
                'label'            => 'GPT Image 2',
                'provider'         => self::PROVIDER_OPENAI,
                'supports_edit'    => true,
                'admin_selectable' => true,
            ],
        ];
    }

    /**
     * Resolve the admin-configured default model, falling back to NANO_BANANA_PRO
     * (the historical default for AI Photoshoot).
     */
    public static function getDefaultModel(): EntityEnum
    {
        $fallback = EntityEnum::NANO_BANANA_PRO;
        $value = setting(self::SETTING_KEY, $fallback->value);

        if (! is_string($value) || ! array_key_exists($value, self::models())) {
            return $fallback;
        }

        $meta = self::models()[$value];

        if (($meta['admin_selectable'] ?? false) === false) {
            return $fallback;
        }

        if (! self::isModelEnabled($value)) {
            return $fallback;
        }

        return EntityEnum::tryFrom($value) ?? $fallback;
    }

    /**
     * Returns 'fal' or 'openai' for the given model. Defaults to FAL for unknown
     * models so existing in-flight FAL requests continue to resolve correctly.
     */
    public static function getProviderFor(EntityEnum $model): string
    {
        return self::models()[$model->value]['provider'] ?? self::PROVIDER_FAL;
    }

    /**
     * Returns whether a model supports image-editing (reference images).
     */
    public static function supportsEdit(EntityEnum $model): bool
    {
        return self::models()[$model->value]['supports_edit'] ?? false;
    }

    /**
     * Returns [enum_value => label] for use in admin select dropdowns.
     *
     * @return array<string, string>
     */
    public static function getModelsForAdminSelect(): array
    {
        $out = [];
        foreach (self::models() as $value => $meta) {
            if (($meta['admin_selectable'] ?? false) !== true) {
                continue;
            }
            $out[$value] = $meta['label'];
        }

        return $out;
    }

    /**
     * Map of OpenAI image models to their global "enabled" setting key and default.
     * Models not in this map are always considered enabled. Reuses the same
     * `enabled_gpt_image_*` flags shared with FashionStudio.
     *
     * @return array<string, array{key: string, default: int}>
     */
    protected static function openAIEnabledFlags(): array
    {
        return [
            EntityEnum::GPT_IMAGE_1->value   => ['key' => 'enabled_gpt_image_1', 'default' => 0],
            EntityEnum::GPT_IMAGE_1_5->value => ['key' => 'enabled_gpt_image_1_5', 'default' => 0],
            EntityEnum::GPT_IMAGE_2->value   => ['key' => 'enabled_gpt_image_2', 'default' => 1],
        ];
    }

    /**
     * Whether the model is enabled globally. OpenAI gpt-image-* respect their
     * `enabled_gpt_image_*` admin toggles; all other models are always enabled.
     */
    public static function isModelEnabled(string $modelValue): bool
    {
        $flag = self::openAIEnabledFlags()[$modelValue] ?? null;

        if ($flag === null) {
            return true;
        }

        return (int) setting($flag['key'], $flag['default']) === 1;
    }

    /**
     * Admin-configured OpenAI image quality. Defaults to the cheapest setting
     * ('low'). Falls back to the default on any invalid stored value.
     */
    public static function getOpenAIQuality(): string
    {
        $value = setting(self::SETTING_OPENAI_QUALITY, self::OPENAI_QUALITY_DEFAULT);

        if (! is_string($value) || ! in_array($value, self::OPENAI_QUALITY_VALUES, true)) {
            return self::OPENAI_QUALITY_DEFAULT;
        }

        return $value;
    }

    /**
     * Admin-configured OpenAI image size. Defaults to the cheapest setting
     * ('1024x1024'). Falls back to the default on any invalid stored value.
     */
    public static function getOpenAISize(): string
    {
        $value = setting(self::SETTING_OPENAI_SIZE, self::OPENAI_SIZE_DEFAULT);

        if (! is_string($value) || ! in_array($value, self::OPENAI_SIZE_VALUES, true)) {
            return self::OPENAI_SIZE_DEFAULT;
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    public static function getOpenAIQualityOptions(): array
    {
        return [
            'low'    => 'Low (cheapest)',
            'medium' => 'Medium',
            'high'   => 'High',
            'auto'   => 'Auto',
        ];
    }

    /**
     * Snap the user's per-account resolution + ratio onto the closest valid
     * OpenAI image size. The OpenAI API only accepts a fixed set of sizes
     * (see OPENAI_SIZE_VALUES), so user picks like 2K portrait or 4K square
     * fall back to the nearest supported dimensions.
     *
     * An `auto` ratio is treated as square so the user's resolution choice
     * is always honored — admin defaults must never overwrite a user pick.
     */
    public static function resolveOpenAISizeForUser(string $resolution, string $ratio): string
    {
        if ($ratio === 'auto') {
            return 'auto';
        }

        if (in_array($ratio, self::OPENAI_SIZE_VALUES, true)) {
            return $ratio;
        }

        $orientation = 'square';
        $parts = explode(':', $ratio);

        if (count($parts) === 2) {
            $w = (int) $parts[0];
            $h = (int) $parts[1];

            if ($w > 0 && $h > 0) {
                $aspect = $w / $h;

                $orientation = match (true) {
                    $aspect >= 1.34 => 'landscape',
                    $aspect <= 0.75 => 'portrait',
                    default         => 'square',
                };
            }
        }

        return match ($resolution) {
            '2K' => match ($orientation) {
                'square'    => '2048x2048',
                'landscape' => '2048x1152',
                'portrait'  => '2048x2048',
            },
            '4K' => match ($orientation) {
                'square'    => '2048x2048',
                'landscape' => '3840x2160',
                'portrait'  => '2160x3840',
            },
            default => match ($orientation) {
                'square'    => '1024x1024',
                'landscape' => '1536x1024',
                'portrait'  => '1024x1536',
            },
        };
    }

    /**
     * @return array<string, string>
     */
    public static function getOpenAISizeOptions(): array
    {
        return [
            '1024x1024' => '1024 x 1024 (square, cheapest)',
            '1024x1536' => '1024 x 1536 (portrait)',
            '1536x1024' => '1536 x 1024 (landscape)',
            '2048x2048' => '2048 x 2048 (square, GPT Image 2)',
            '2048x1152' => '2048 x 1152 (landscape, GPT Image 2)',
            '3840x2160' => '3840 x 2160 (4K landscape, GPT Image 2)',
            '2160x3840' => '2160 x 3840 (4K portrait, GPT Image 2)',
            'auto'      => 'Auto',
        ];
    }

    /**
     * Admin-configured default aspect ratio for nano-banana / FAL models.
     */
    public static function getFalRatio(): string
    {
        $value = setting(self::SETTING_FAL_RATIO, self::FAL_RATIO_DEFAULT);

        if (! is_string($value) || ! in_array($value, self::FAL_RATIO_VALUES, true)) {
            return self::FAL_RATIO_DEFAULT;
        }

        return $value;
    }

    /**
     * Admin-configured default resolution for nano-banana / FAL models.
     */
    public static function getFalResolution(): string
    {
        $value = setting(self::SETTING_FAL_RESOLUTION, self::FAL_RESOLUTION_DEFAULT);

        if (! is_string($value) || ! in_array($value, self::FAL_RESOLUTION_VALUES, true)) {
            return self::FAL_RESOLUTION_DEFAULT;
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    public static function getFalRatioOptions(): array
    {
        return [
            'auto' => 'Auto',
            '1:1'  => '1:1 (Square)',
            '16:9' => '16:9 (Widescreen)',
            '9:16' => '9:16 (Vertical)',
            '4:3'  => '4:3 (Standard Landscape)',
            '3:4'  => '3:4 (Standard Portrait)',
            '3:2'  => '3:2 (Landscape)',
            '2:3'  => '2:3 (Portrait)',
            '5:4'  => '5:4 (Landscape)',
            '4:5'  => '4:5 (Portrait)',
            '21:9' => '21:9 (Cinematic)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getFalResolutionOptions(): array
    {
        return [
            '1K' => '1K (cheapest)',
            '2K' => '2K',
            '4K' => '4K',
        ];
    }

    /**
     * Aspect ratios the given provider can produce *exactly*. The user-facing
     * dropdown should only show these — selecting a ratio outside this list
     * for the active provider would silently snap to a different output, which
     * is the regression we are fixing here.
     *
     * OpenAI's gpt-image-* family only accepts a fixed set of `size` strings,
     * so the OpenAI dropdown emits those size strings directly (rather than
     * W:H ratios) — that way the user picks the exact output and we skip the
     * resolution+ratio snapping step. Legacy stored W:H values still resolve
     * via resolveOpenAISizeForUser().
     *
     * @return array<string, string>
     */
    public static function getRatioOptionsForProvider(string $provider): array
    {
        return match ($provider) {
            self::PROVIDER_OPENAI => [
                'auto'      => 'Auto',
                '1024x1024' => 'Square',
                '2048x2048' => 'Square HD',
                '1536x1024' => 'Landscape 3:2',
                '2048x1152' => 'Landscape 16:9',
                '3840x2160' => 'Landscape 16:9 4K',
                '1024x1536' => 'Portrait 2:3',
                '2160x3840' => 'Portrait 9:16 4K',
            ],
            default => self::getFalRatioOptions(),
        };
    }

    /**
     * Ratio options for the admin-configured default model's provider.
     *
     * @return array<string, string>
     */
    public static function getRatioOptionsForActiveModel(): array
    {
        return self::getRatioOptionsForProvider(
            self::getProviderFor(self::getDefaultModel())
        );
    }

    /**
     * Normalize a stored user ratio against the active provider's option keys.
     *
     * Users can have legacy ratios (e.g. `1:1` from a previous FAL run) saved
     * after the admin switches to OpenAI, whose dropdown emits `auto`, `1024x1024`,
     * etc. A `<select x-model="ratio">` bound to a value with no matching option
     * shows the first option visually but keeps the stale value in the model,
     * so submitting without touching the dropdown sends the invalid ratio and
     * fails the `in:` rule. Falling back to `auto` keeps the UI and submitted
     * value in sync.
     */
    public static function normalizeRatioForActiveModel(?string $ratio): string
    {
        $options = self::getRatioOptionsForActiveModel();

        if ($ratio !== null && array_key_exists($ratio, $options)) {
            return $ratio;
        }

        if (array_key_exists('auto', $options)) {
            return 'auto';
        }

        return (string) array_key_first($options);
    }
}
