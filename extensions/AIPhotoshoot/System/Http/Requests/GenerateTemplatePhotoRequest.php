<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Requests;

use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateTemplatePhotoRequest extends FormRequest
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
        $templateSlugs = array_column(config('ai-photoshoot.templates', []), 'slug');
        $maxTemplates = (int) config('ai-photoshoot.max_images', 4);

        return [
            'product_image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:' . AIPhotoshootImageModelRegistry::getMaxUploadSizeKb()],
            'templates'     => ['required', 'array', 'min:1', 'max:' . $maxTemplates],
            'templates.*'   => ['required', 'string', Rule::in($templateSlugs)],
            'ratio'         => ['nullable', 'string', 'in:' . implode(',', array_keys(AIPhotoshootImageModelRegistry::getRatioOptionsForActiveModel()))],
            'lock_key'      => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxTemplates = (int) config('ai-photoshoot.max_images', 4);

        return [
            'product_image.required' => __('A product image is required.'),
            'product_image.image'    => __('The product image must be a valid image file.'),
            'product_image.max'      => __('The product image may not be larger than :size MB.', ['size' => AIPhotoshootImageModelRegistry::getMaxUploadSizeMb()]),
            'templates.required'     => __('Please pick at least one template.'),
            'templates.array'        => __('Templates must be sent as a list.'),
            'templates.min'          => __('Please pick at least one template.'),
            'templates.max'          => __('You can select up to :n templates at once.', ['n' => $maxTemplates]),
            'templates.*.in'         => __('One of the selected templates is not valid.'),
        ];
    }
}
