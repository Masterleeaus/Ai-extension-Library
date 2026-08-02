<?php

declare(strict_types=1);

namespace App\Extensions\CreativeSuiteAnnotations\System\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreativeSuiteAnnotationsEditRequest extends FormRequest
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
            'image_reference' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp'],
            'mask'            => ['required', 'file', 'image', 'mimes:png'],
            'tool'            => ['required', 'string', 'in:comment,rect,oval,lasso,brush,replace-text'],
            'prompt'          => ['required_unless:tool,comment,replace-text', 'nullable', 'string', 'max:2000'],
            // Comment tool ships raw per-pin prompts as a JSON string array;
            // the backend composes the final wrapped prompt server-side.
            'pin_prompts'     => ['required_if:tool,comment', 'nullable', 'string', 'max:8000'],
            // Replace-text tool ships per-pin {original_text, new_text} pairs
            // as a JSON string array; the backend wraps them with
            // numbered-marker scaffolding before calling the image model.
            'text_edits'      => ['required_if:tool,replace-text', 'nullable', 'string', 'max:16000'],
        ];
    }
}
