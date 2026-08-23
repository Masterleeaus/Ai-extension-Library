<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PublishToChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_public_id' => ['required', 'string'],
            'channel_name' => ['required', 'string', 'in:web,app,pos,marketplace'],
            'is_published' => ['nullable', 'boolean'],
            'channel_price' => ['nullable', 'numeric', 'min:0'],
            'channel_sku' => ['nullable', 'string', 'max:100'],
            'is_visible' => ['nullable', 'boolean'],
            'scheduled_for' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'channel_metadata' => ['nullable', 'array'],
        ];
    }
}
