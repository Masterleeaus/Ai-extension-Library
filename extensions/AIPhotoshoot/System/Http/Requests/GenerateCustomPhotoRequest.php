<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Requests;

use App\Extensions\AIPhotoshoot\System\Models\AIPhotoshootUserSetting;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry;
use Illuminate\Foundation\Http\FormRequest;

class GenerateCustomPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prompt'        => ['required', 'string', 'max:2000'],
            'product_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:' . AIPhotoshootImageModelRegistry::getMaxUploadSizeKb()],
            'ratio'         => ['nullable', 'string', 'in:' . implode(',', array_keys(AIPhotoshootImageModelRegistry::getRatioOptionsForActiveModel()))],
            'num_images'    => ['nullable', 'integer', 'min:1', 'max:' . AIPhotoshootUserSetting::MAX_NUM_IMAGES],
            'lock_key'      => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prompt.required'     => __('Please describe the photoshoot you want to generate.'),
            'prompt.max'          => __('The prompt may not be longer than :max characters.'),
            'product_image.image' => __('The product image must be a valid image file.'),
            'product_image.max'   => __('The product image may not be larger than :size MB.', ['size' => AIPhotoshootImageModelRegistry::getMaxUploadSizeMb()]),
        ];
    }
}
