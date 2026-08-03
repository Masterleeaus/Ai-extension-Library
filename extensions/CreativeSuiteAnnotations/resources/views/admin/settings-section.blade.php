@php
    use App\Domains\Entity\Enums\EntityEnum;
    use App\Extensions\CreativeSuiteAnnotations\System\Http\Controllers\CreativeSuiteAnnotationsAIController;

    $csaModels = collect([
        EntityEnum::GPT_IMAGE_2,
        EntityEnum::GPT_IMAGE_1_5,
        EntityEnum::NANO_BANANA_PRO,
        EntityEnum::NANO_BANANA_2,
        EntityEnum::NANO_BANANA,
        EntityEnum::GROK_IMAGINE_IMAGE,
        EntityEnum::FLUX_PRO_KONTEXT,
        EntityEnum::FLUX_PRO_KONTEXT_MAX_MULTI,
    ])
        ->map(
            fn($entity) => [
                'value' => $entity->value,
                'label' => $entity->label(),
            ],
        )
        ->all();

    $csaCurrentModel = setting('creative_suite_annotations_ai_model', config('creative-suite-annotations.default_model', 'gpt-image-2'));

    $csaVisionModels = collect(CreativeSuiteAnnotationsAIController::VISION_CAPABLE_MODELS)->map(fn($entity) => ['value' => $entity->value, 'label' => $entity->label()])->all();

    $csaCurrentVisionModel = setting('creative_suite_annotations_vision_model', '');
@endphp

<form
    action="{{ route('dashboard.admin.creative-suite-annotations.settings.update') }}"
    method="POST"
>
    @csrf

    {{-- Parent Creative Suite section. The parent view delegates rendering --}}
    {{-- to this file when the annotations extension is installed so that --}}
    {{-- one form + one save button covers both extensions' settings. --}}
    @isset($parentModels)
        <h3 class="mb-[25px] text-[20px]">{{ __('Creative Suite') }}</h3>

        @include('creative-suite::setting.particles.ai-tools-settings')
    @endisset

    <h3 class="mb-2.5 mt-10 border-t pt-8 text-[20px]">
        {{ __('creative-suite-annotations::creative-suite-annotations.settings.title') }}
    </h3>
    <p class="text-muted mb-[25px]">
        {{ __('creative-suite-annotations::creative-suite-annotations.settings.subtitle') }}
    </p>

    <div class="space-y-5">
        <x-forms.input
            id="creative_suite_annotations_ai_model"
            type="select"
            size="lg"
            label="{{ __('creative-suite-annotations::creative-suite-annotations.settings.model_label') }}"
            tooltip="{{ __('creative-suite-annotations::creative-suite-annotations.settings.model_help') }}"
            name="creative_suite_annotations_ai_model"
            required
        >
            @foreach ($csaModels as $model)
                <option
                    value="{{ $model['value'] }}"
                    @selected($csaCurrentModel === $model['value'])
                >{{ $model['label'] }}</option>
            @endforeach
        </x-forms.input>

        <x-forms.input
            id="creative_suite_annotations_vision_model"
            type="select"
            size="lg"
            label="{{ __('creative-suite-annotations::creative-suite-annotations.settings.vision_model_label') }}"
            tooltip="{{ __('creative-suite-annotations::creative-suite-annotations.settings.vision_model_help') }}"
            name="creative_suite_annotations_vision_model"
        >
            <option
                value=""
                @selected($csaCurrentVisionModel === '')
            >
                {{ __('creative-suite-annotations::creative-suite-annotations.settings.vision_model_auto') }}
            </option>
            @foreach ($csaVisionModels as $model)
                <option
                    value="{{ $model['value'] }}"
                    @selected($csaCurrentVisionModel === $model['value'])
                >{{ $model['label'] }}</option>
            @endforeach
        </x-forms.input>

        <x-button
            class="w-full"
            size="lg"
            type="submit"
        >
            {{ __('creative-suite-annotations::creative-suite-annotations.settings.save') }}
        </x-button>
    </div>
</form>
