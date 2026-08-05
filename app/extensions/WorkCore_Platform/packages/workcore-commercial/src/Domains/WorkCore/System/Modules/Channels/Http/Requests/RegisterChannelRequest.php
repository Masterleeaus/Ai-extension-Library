<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_channels');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:airbnb,booking,amazon,ebay,delivery'],
            'api_token' => ['required', 'string'],
            'api_secret' => ['nullable', 'string'],
            'credentials' => ['nullable', 'array'],
            'enabled' => ['boolean'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Channel name is required',
            'type.required' => 'Channel type is required',
            'type.in' => 'Invalid channel type',
            'api_token.required' => 'API token is required',
        ];
    }
}
