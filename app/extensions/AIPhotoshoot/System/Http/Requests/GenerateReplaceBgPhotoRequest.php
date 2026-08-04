<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Requests;

use App\Extensions\AIPhotoshoot\System\Models\AIPhotoshootUserSetting;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry;
use Illuminate\Foundation\Http\FormRequest;

class GenerateReplaceBgPhotoRequest extends FormRequest
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
            'product_image'    => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:' . AIPhotoshootImageModelRegistry::getMaxUploadSizeKb()],
            'background_id'    => ['nullable', 'string'],
            'background_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:' . AIPhotoshootImageModelRegistry::getMaxUploadSizeKb()],
            'ratio'            => ['nullable', 'string', 'in:' . implode(',', array_keys(AIPhotoshootImageModelRegistry::getRatioOptionsForActiveModel()))],
            'num_images'       => ['nullable', 'integer', 'min:1', 'max:' . AIPhotoshootUserSetting::MAX_NUM_IMAGES],
            'lock_key'         => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_image.required' => __('A product image is required.'),
            'product_image.image'    => __('The product image must be a valid image file.'),
            'product_image.max'      => __('The product image may not be larger than :size MB.', ['size' => AIPhotoshootImageModelRegistry::getMaxUploadSizeMb()]),
        ];
    }

    protected function prepareForValidation(): void
    {
        // Require one of background_id or background_image.
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('background_id') && ! $this->hasFile('background_image')) {
                $validator->errors()->add(
                    'background_id',
                    __('Please choose a background preset, a saved background, or upload one.')
                );
            }
        });
    }
}
