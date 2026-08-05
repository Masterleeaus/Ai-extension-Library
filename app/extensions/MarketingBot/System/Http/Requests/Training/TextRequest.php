<?php

declare(strict_types=1);


namespace App\Extensions\MarketingBot\System\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;

class TextRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id'      => 'required',
            'title'   => ['required', 'string'],
            'content' => ['required', 'string'],
        ];
    }
}
