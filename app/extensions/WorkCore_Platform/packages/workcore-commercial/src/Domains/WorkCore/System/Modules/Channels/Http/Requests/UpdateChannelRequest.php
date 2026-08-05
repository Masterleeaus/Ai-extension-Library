<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_channels');
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'api_token' => ['nullable', 'string'],
            'api_secret' => ['nullable', 'string'],
            'credentials' => ['nullable', 'array'],
            'enabled' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
