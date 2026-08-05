<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_public_id' => ['required', 'string'],
            'variant_name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:workcore_catalog_product_variants,sku'],
            'variant_attributes' => ['nullable', 'array'],
            'price_modifier' => ['nullable', 'numeric'],
            'cost_modifier' => ['nullable', 'numeric'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
