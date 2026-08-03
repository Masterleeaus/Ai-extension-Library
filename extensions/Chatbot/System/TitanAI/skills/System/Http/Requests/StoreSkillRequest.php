<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        if ($this->hasFile('file')) {
            $maxKilobytes = (int) config('system-ai-chat-skills.imports.max_upload_kilobytes', 5120);

            return [
                'ownership_type' => ['sometimes', 'in:user,company'],
                'visibility' => ['sometimes', 'in:company_private,company_shared'],
                'file' => ['required', 'file', 'max:' . $maxKilobytes, function ($attribute, $value, $fail): void {
                    $extension = strtolower((string) $value->getClientOriginalExtension());
                    $mime = strtolower((string) $value->getMimeType());
                    $allowedExtensions = ['zip', 'md', 'txt', 'markdown', 'skill'];

                    if (! in_array($extension, $allowedExtensions, true)) {
                        $fail(__('Only .zip, .skill, and .md files are accepted.'));
                        return;
                    }

                    $path = $value->getRealPath();
                    $header = is_string($path) && is_file($path)
                        ? (string) file_get_contents($path, false, null, 0, 4)
                        : '';
                    $hasZipMagic = $header === "PK\x03\x04";

                    if (in_array($extension, ['zip', 'skill'], true)) {
                        if (! $hasZipMagic || ! in_array($mime, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'], true)) {
                            $fail(__('The uploaded archive does not contain a valid ZIP package.'));
                        }
                        return;
                    }

                    if ($hasZipMagic || ! in_array($mime, ['text/plain', 'text/markdown', 'text/x-markdown', 'application/octet-stream'], true)) {
                        $fail(__('The uploaded Markdown skill file has an unexpected content type.'));
                    }
                }],
            ];
        }

        return [
            'ownership_type' => ['sometimes', 'in:user,company'],
            'visibility' => ['sometimes', 'in:company_private,company_shared'],
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string', 'max:2000'],
            'instructions' => ['required', 'string', 'max:50000'],
            'version' => ['sometimes', 'string', 'regex:/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $maxKilobytes = (int) config('system-ai-chat-skills.imports.max_upload_kilobytes', 5120);

        return [
            'file.max' => __('The skill file must not be larger than :size KB.', ['size' => $maxKilobytes]),
        ];
    }
}
