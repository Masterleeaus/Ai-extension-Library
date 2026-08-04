<?php

namespace App\Extensions\CreativeSuiteAITemplate\System\Http\Requests;

use App\Helpers\Classes\MarketplaceHelper;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class TemplateGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check()
            && MarketplaceHelper::isRegistered('creative-suite')
            && MarketplaceHelper::isRegistered('creative-suite-ai-template');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'template_description'  => ['required', 'string', 'max:2000'],
            'template_aspect_ratio' => ['required', 'string', 'in:1:1,16:9,9:16,4:5,5:4'],
            'template_reference'    => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ];
    }
}
