@php
    $csTemplateEngines = [
        '' => __('Default'),
        'openai' => 'OpenAI',
        'anthropic' => 'Anthropic',
        'gemini' => 'Gemini',
        'deep_seek' => 'DeepSeek',
        'x_ai' => 'X.AI',
    ];

    $csTemplateImageModels = [
        'gpt-image-1' => 'GPT Image 1 (OpenAI)',
        'gpt-image-1.5' => 'GPT Image 1.5 (OpenAI)',
        'gpt-image-2' => 'GPT Image 2 (OpenAI)',
        'flux-pro/kontext/text-to-image' => 'Flux Pro Kontext (FalAI)',
        'nano-banana-pro' => 'Nano Banana Pro (FalAI)',
        'nano-banana-2' => 'Nano Banana 2 (FalAI)',
        'flux/schnell' => 'Flux Schnell (FalAI)',
    ];
@endphp
<div class="space-y-5">
    <x-forms.input
        id="creative_suite_template_engine"
        type="select"
        size="lg"
        label="{{ __('Creative Suite Template Engine') }}"
        tooltip="{{ __('AI engine used for generating templates from text descriptions in Creative Suite.') }}"
        name="creative_suite_template_engine"
    >
        @foreach ($csTemplateEngines as $key => $label)
            <option
                value="{{ $key }}"
                {{ setting('creative_suite_template_engine', '') === $key ? 'selected' : null }}
            >
                {{ $label }}
            </option>
        @endforeach
    </x-forms.input>

    <x-forms.input
        id="creative_suite_template_image_model"
        type="select"
        size="lg"
        label="{{ __('Creative Suite Template Image Model') }}"
        tooltip="{{ __('AI model used to generate images for template placeholders. GPT Image models are recommended.') }}"
        name="creative_suite_template_image_model"
    >
        @foreach ($csTemplateImageModels as $key => $label)
            <option
                value="{{ $key }}"
                {{ setting('creative_suite_template_image_model', 'gpt-image-1') === $key ? 'selected' : null }}
            >
                {{ $label }}
            </option>
        @endforeach
    </x-forms.input>
</div>
