<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'                     => 'required|string|max:255',
            'social_media_platform_id' => 'required|exists:ext_social_media_platforms,id',
            'trigger_target'           => 'required|in:specific_post,all_posts',
            'trigger_post_id'          => 'nullable|required_if:trigger_target,specific_post|string',
            'trigger_post_data'        => 'nullable|array',
            'keyword_mode'             => 'required|in:any,specific',
            'include_keywords'         => 'nullable|array',
            'include_keywords.*'       => 'string|max:100',
            'exclude_keywords'         => 'nullable|array',
            'exclude_keywords.*'       => 'string|max:100',
            'enable_public_replies'    => 'boolean',
            'delay_seconds'            => 'integer|min:0|max:300',
            'workflow_graph'           => 'nullable|array',
            'actions'                  => 'required|array|min:1',
            'actions.*.type'           => 'required|in:text,button,image,quick_replies,delay',
            'actions.*.content'        => 'required|array',
            'replies'                  => 'nullable|array',
            'replies.*.content'        => 'required_with:replies|string',
        ];
    }
}
