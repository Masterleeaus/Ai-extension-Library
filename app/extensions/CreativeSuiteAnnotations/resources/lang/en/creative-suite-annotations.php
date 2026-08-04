<?php

return [
    'demo_disabled' => 'This feature is disabled in demo mode.',
    'no_credits'    => 'You have no credits left. Please consider upgrading your plan.',
    'in_progress'   => 'Another annotation is being processed. Please try again in a moment.',
    'generated'     => 'Annotation submitted successfully.',
    'settings'      => [
        'title'              => 'Creative Suite Annotations',
        'subtitle'           => 'Configure the default AI model used for annotation submissions. Plan-level access is managed in the Pricing Plan editor.',
        'model_label'        => 'Default AI Model',
        'model_help'         => 'Used for every annotation submission. Mask-capable models receive the binary mask; other models receive a cropped region and the result is composited client-side.',
        'vision_model_label' => 'Vision Model (Replace Text)',
        'vision_model_help'  => 'Used by the Replace Text tool to detect text blocks in an image. Auto picks the first available model from a recommended list — typically GPT-5.4 Mini, falling back to other current-generation vision models if it is not configured.',
        'vision_model_auto'  => 'Auto (recommended)',
        'save'               => 'Save',
        'saved'              => 'Annotation settings updated.',
    ],
];
