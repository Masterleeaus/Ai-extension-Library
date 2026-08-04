<?php

declare(strict_types=1);

namespace App\Extensions\CreativeSuiteAnnotations\System\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreativeSuiteAnnotationsAnalyzeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp'],
        ];
    }
}
