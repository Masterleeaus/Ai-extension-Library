<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreServicePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_active' => ['nullable', 'boolean'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['string'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
