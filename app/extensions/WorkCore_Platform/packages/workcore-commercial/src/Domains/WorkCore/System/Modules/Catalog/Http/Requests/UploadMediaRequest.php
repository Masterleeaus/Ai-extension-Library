<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_public_id' => ['nullable', 'string'],
            'url' => ['required', 'string', 'url', 'max:500'],
            'media_type' => ['required', 'string', 'in:image,video,document'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_primary' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
