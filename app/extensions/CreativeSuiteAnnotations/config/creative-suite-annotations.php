<?php

return [
    'version' => 1.0,

    /*
    | Models that natively accept a binary mask alongside the source image.
    | When the configured `creative_suite_annotations_ai_model` is in this
    | list, the controller will forward image + mask to the provider.
    | Otherwise the client crops the masked region, the provider edits the
    | crop only, and the client composites the result back into place.
    */
    /*
    | Models that natively accept a binary mask alongside the source image.
    | When the configured `creative_suite_annotations_ai_model` is in this
    | list the controller forwards image + mask to OpenAI's images/edits
    | endpoint. Otherwise the server crops the masked region, edits the
    | crop via the provider, and composites the result back into the full
    | image before returning.
    */
    'mask_capable_models' => [
        // OpenAI: native mask parameter on /v1/images/edits.
        'gpt-image-1',
        'gpt-image-1.5',
        'gpt-image-2',
        'dall-e-2',

        // Nano Banana (Gemini 2.5/3 Flash Image / Pro): mask via multi-image
        // input — source + black/white mask + prompt that names the mask.
        'nano-banana',
        'nano-banana/edit',
        'nano-banana-pro',
        'nano-banana-pro/edit',
        'nano-banana-2',
        'nano-banana-2/edit',
    ],

    'default_model' => 'gpt-image-2',
];
