<?php

declare(strict_types=1);

namespace App\Extensions\CreativeSuiteAnnotations\System\Http\Controllers\Admin;

use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CreativeSuiteAnnotationsSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        // The unified settings page also POSTs the parent Creative Suite
        // model field through this endpoint when the annotations extension
        // is installed (so a single Save button covers both). The parent
        // field is `nullable` here — when the page is rendered without it
        // (e.g. partial includes), the existing setting is preserved.
        $validated = $request->validate([
            'creative_suite_ai_model'                 => ['nullable', 'string'],
            'creative_suite_template_engine'          => ['nullable', 'string'],
            'creative_suite_template_image_model'     => ['nullable', 'string'],
            'creative_suite_annotations_ai_model'     => ['required', 'string'],
            'creative_suite_annotations_vision_model' => ['nullable', 'string'],
        ]);

        if (Helper::appIsNotDemo()) {
            $payload = [
                'creative_suite_annotations_ai_model'     => $validated['creative_suite_annotations_ai_model'],
                'creative_suite_annotations_vision_model' => $validated['creative_suite_annotations_vision_model'] ?? '',
            ];

            if (! empty($validated['creative_suite_ai_model'])) {
                $payload['creative_suite_ai_model'] = $validated['creative_suite_ai_model'];
            }

            if ($request->has('creative_suite_template_engine')) {
                $payload['creative_suite_template_engine'] = $validated['creative_suite_template_engine'] ?? '';
            }

            if ($request->has('creative_suite_template_image_model')) {
                $payload['creative_suite_template_image_model'] = $validated['creative_suite_template_image_model'] ?? 'gpt-image-1';
            }

            setting($payload)->save();
        }

        return back()->with([
            'type'    => 'success',
            'message' => __('creative-suite-annotations::creative-suite-annotations.settings.saved'),
        ]);
    }
}
