<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:workcore_catalog_products,sku'],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'category_public_id' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'product_type' => ['nullable', 'string', 'in:standard,variable,bundle,service'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'attributes' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
