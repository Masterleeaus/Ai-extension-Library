<?php

declare(strict_types=1);


namespace App\Extensions\ProductPhotography\System\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductPhotographyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image'      => 'required',
            'background' => 'required',
        ];
    }
}
