<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'sku' => ['sometimes', 'string', 'max:100', "unique:workcore_catalog_products,sku,{$productId},public_id"],
            'description' => ['nullable', 'string'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'category_public_id' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'product_type' => ['nullable', 'string', 'in:standard,variable,bundle,service'],
            'attributes' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
