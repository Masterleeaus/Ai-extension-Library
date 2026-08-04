@php
    $style = $style ?? 'default';
    $aspect_ratios = [
        [
            'label' => '1:1 (Square)',
            'value' => '1:1',
        ],
        [
            'label' => '16:9 (Landscape)',
            'value' => '16:9',
        ],
        [
            'label' => '9:16 (Portrait)',
            'value' => '9:16',
        ],
        [
            'label' => '5:4 (Landscape)',
            'value' => '5:4',
        ],
        [
            'label' => '4:5 (Portrait)',
            'value' => '4:5',
        ],
    ];
@endphp

<form
    @class([
        'mx-auto flex flex-col',
        'w-[min(100%,720px)] rounded-[22px] bg-background shadow-[0_4px_55px_hsl(0_0%_0%/6%)]' =>
            $style === 'default',
        'w-full h-full' => $style === 'expanded',
    ])
    x-ref="aiTemplateForm"
    @submit.prevent="generateTemplate"
>
    @csrf

    <x-forms.input
        class:container="grow max-sm:w-full"
        @class([
            'resize-none rounded-none !border-none !bg-transparent px-6 py-4 !outline-none !ring-0 placeholder:text-foreground/50 sm:text-sm disabled:opacity-50',
            'h-[67px]' => $style === 'default',
            'h-[90px]' => $style === 'expanded',
        ])
        type="textarea"
        name="template_description"
        x-ref="templateDescription"
        x-model="prompt"
        placeholder="{{ $style === 'expanded' ? __('e.g. Create a clean, cozy Instagram Story for a coffee shop promotion (“Buy 1 Get 1 Free”). Use cozy coffee visuals and bold readable text)') : __('or explain your idea to create a template') }}"
        required
        @keydown.enter="if ( !$event.shiftKey ) { $event.preventDefault(); $el.form.requestSubmit(); }"
    />

    <div class="flex grow items-center justify-between gap-3 px-5 pb-2.5">
        <div class="flex h-14 grow items-center gap-3 py-2 max-md:overflow-x-auto max-md:overflow-y-hidden">
            <div
                class="group relative shrink-0"
                x-cloak
                x-show="lastGeneratedReferenceImage"
            >
                <a
                    :data-fslightbox="lastGeneratedReferenceImage"
                    :href="lastGeneratedReferenceImage"
                    x-init="$watch('lastGeneratedReferenceImage', () => { if (lastGeneratedReferenceImage) { refreshFsLightbox(); } })"
                >
                    <img
                        class="size-[38px] rounded-full object-cover"
                        :src="lastGeneratedReferenceImage"
                        alt="{{ __('Reference') }}"
                    />
                </a>
                <button
                    class="absolute -end-1.5 -top-1.5 grid size-5 place-items-center rounded-full bg-background text-foreground/70 opacity-0 shadow-sm transition-opacity hover:scale-105 hover:bg-red-500 hover:text-white group-hover:opacity-100"
                    type="button"
                    @click="lastGeneratedReferenceImage = null; $refs.templateRefInput && ($refs.templateRefInput.value = '')"
                    title="{{ __('Remove') }}"
                >
                    <x-tabler-x class="size-3" />
                </button>
            </div>

            <label
                class="inline-grid size-[38px] shrink-0 cursor-pointer place-items-center rounded-button border-2 transition hover:-translate-y-0.5 hover:border-primary hover:bg-primary hover:text-primary-foreground hover:shadow-lg hover:shadow-black/5 [&.disabled]:pointer-events-none [&.disabled]:opacity-50"
                title="{{ __('Upload reference image (optional)') }}"
                :class="{ 'disabled': generateAiReference }"
            >
                <x-tabler-plus class="size-4 shrink-0" />
                <input
                    class="hidden"
                    type="file"
                    name="template_reference"
                    accept="image/jpeg,image/png,image/jpg"
                    x-ref="templateRefInput"
                    @change="lastGeneratedReferenceImage = $el.files?.length ? URL.createObjectURL($el.files[0]) : null"
                />
            </label>

            @if (setting('user_prompt_library') == null || setting('user_prompt_library'))
                <x-button
                    class="placeitem-center inline-grid size-[38px] shrink-0"
                    title="{{ __('Browse pre-defined prompts') }}"
                    variant="outline"
                    hover-variant="primary"
                    size="none"
                    @click.prevent="togglePromptLibraryShow"
                >
                    <svg
                        width="19"
                        height="20"
                        viewBox="0 0 19 20"
                        fill="currentColor"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M1 16.0212C1.1795 15.9071 1.37075 15.8109 1.57375 15.7327C1.77675 15.6546 1.99575 15.6155 2.23075 15.6155H3.3845V3H2.23075C1.88208 3 1.58975 3.1215 1.35375 3.3645C1.11792 3.60733 1 3.89608 1 4.23075V16.0212ZM2.23075 20C1.61108 20 1.08442 19.7868 0.65075 19.3605C0.216917 18.9343 0 18.4167 0 17.8078V4.23075C0 3.61108 0.216917 3.08442 0.65075 2.65075C1.08442 2.21692 1.61108 2 2.23075 2H9.5V3H4.3845V15.6155H11.6155V11.5H12.6155V16.6155H2.23075C1.89608 16.6155 1.60733 16.7294 1.3645 16.9573C1.1215 17.1851 1 17.4674 1 17.8043C1 18.1411 1.1215 18.4246 1.3645 18.6548C1.60733 18.8849 1.89608 19 2.23075 19H15V10.5H16V20H2.23075ZM13.5 10.5C13.5 9.106 13.9848 7.92417 14.9545 6.9545C15.9242 5.98483 17.106 5.5 18.5 5.5C17.106 5.5 15.9242 5.01517 14.9545 4.0455C13.9848 3.07583 13.5 1.894 13.5 0.5C13.5 1.894 13.0152 3.07583 12.0455 4.0455C11.0758 5.01517 9.894 5.5 8.5 5.5C9.894 5.5 11.0758 5.98483 12.0455 6.9545C13.0152 7.92417 13.5 9.106 13.5 10.5Z"
                        />
                    </svg>
                </x-button>
            @endif

            <label
                @class([
                    'relative cursor-pointer rounded-button whitespace-nowrap border-2 text-2xs font-medium transition hover:-translate-y-0.5 hover:border-primary hover:bg-primary hover:text-primary-foreground hover:shadow-lg hover:shadow-black/5 has-[input:checked]:border-primary has-[input:checked]:bg-primary has-[input:checked]:text-primary-foreground',
                    'inline-flex items-center justify-center gap-2 px-4 py-2' =>
                        $style === 'expanded',
                    'inline-grid size-[38px] place-items-center' => $style === 'default',
                ])
                title="{{ __('Generate Reference') }}"
            >
                <input
                    class="absolute inset-0 size-full cursor-pointer opacity-0"
                    type="checkbox"
                    name="generate_ai_reference"
                    value="1"
                    x-model="generateAiReference"
                    @change="if (generateAiReference) { $refs.templateRefInput && ($refs.templateRefInput.value = ''); lastGeneratedReferenceImage = null; }"
                />
                <svg
                    class="size-4 shrink-0"
                    width="17"
                    height="17"
                    viewBox="0 0 17 17"
                    fill="currentColor"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M1.73243 16.2917C1.24831 16.2917 0.838542 16.124 0.503125 15.7885C0.167708 15.4531 0 15.0434 0 14.5592V9.10417H1.4375V14.5592C1.4375 14.633 1.46825 14.7006 1.52974 14.7619C1.59107 14.8234 1.65864 14.8542 1.73243 14.8542H7.1875V16.2917H1.73243ZM9.10417 16.2917V14.8542H14.5592C14.633 14.8542 14.7006 14.8234 14.7619 14.7619C14.8234 14.7006 14.8542 14.633 14.8542 14.5592V9.10417H16.2917V14.5592C16.2917 15.0434 16.124 15.4531 15.7885 15.7885C15.4531 16.124 15.0434 16.2917 14.5592 16.2917H9.10417ZM3.11458 12.6979L5.49197 9.54644L7.40864 11.9976L10.0994 8.4961L13.2506 12.6979H3.11458ZM0 7.1875V1.73243C0 1.24831 0.167708 0.838542 0.503125 0.503125C0.838542 0.167708 1.24831 0 1.73243 0H7.1875V1.4375H1.73243C1.65864 1.4375 1.59107 1.46825 1.52974 1.52974C1.46825 1.59107 1.4375 1.65864 1.4375 1.73243V7.1875H0ZM14.8542 7.1875V1.73243C14.8542 1.65864 14.8234 1.59107 14.7619 1.52974C14.7006 1.46825 14.633 1.4375 14.5592 1.4375H9.10417V0H14.5592C15.0434 0 15.4531 0.167708 15.7885 0.503125C16.124 0.838542 16.2917 1.24831 16.2917 1.73243V7.1875H14.8542ZM11.4078 6.13693C11.0479 6.13693 10.7491 6.01809 10.5112 5.78043C10.2736 5.5426 10.1547 5.24376 10.1547 4.88391C10.1547 4.52389 10.2736 4.22497 10.5112 3.98715C10.7491 3.74948 11.0479 3.63065 11.4078 3.63065C11.7678 3.63065 12.0667 3.74948 12.3045 3.98715C12.5422 4.22497 12.661 4.52389 12.661 4.88391C12.661 5.24376 12.5422 5.5426 12.3045 5.78043C12.0667 6.01809 11.7678 6.13693 11.4078 6.13693Z"
                    />
                </svg>
                @if ($style === 'expanded')
                    {{ __('Generate Reference') }}
                @endif
            </label>

            <x-dropdown.dropdown
                class:dropdown-dropdown="backdrop-blur-lg rounded-dropdown"
                anchor="start"
                offsetY="8px"
            >
                <x-slot:trigger
                    class="min-h-[38px] min-w-[38px] whitespace-nowrap px-4 text-2xs font-medium outline-2 outline-foreground/5 group-[&.lqd-is-active]/dropdown:bg-primary group-[&.lqd-is-active]/dropdown:text-primary-foreground group-[&.lqd-is-active]/dropdown:outline-primary max-sm:whitespace-nowrap"
                    variant="outline"
                >
                    <span x-text="generateAiReferenceAspectRatio">
                        {{ $aspect_ratios[0]['label'] }}
                    </span>
                </x-slot:trigger>

                <x-slot:dropdown
                    class="max-h-60 min-w-[min(240px,100vw)] !transform-none space-y-1 overflow-y-auto rounded-lg border-none bg-background/50 p-2 shadow-xl shadow-black/10"
                >
                    <p class="m-0 px-4 py-2 text-2xs font-medium opacity-50">
                        {{ __('Aspect Ratio') }}
                    </p>

                    @foreach ($aspect_ratios as $aspect_ratio)
                        <button
                            class="flex w-full items-center justify-between gap-2 rounded-lg px-4 py-3 text-start text-xs transition hover:bg-foreground/5 [&.selected]:bg-foreground/5"
                            type="button"
                            @click.prevent="generateAiReferenceAspectRatio = '{{ $aspect_ratio['value'] }}'; toggle('collapse')"
                            :class="{ 'selected': generateAiReferenceAspectRatio === '{{ $aspect_ratio['value'] }}' }"
                        >
                            {{ $aspect_ratio['label'] }}
                            <template x-if="generateAiReferenceAspectRatio === '{{ $aspect_ratio['value'] }}'">
                                <x-tabler-check class="size-5 shrink-0" />
                            </template>
                        </button>
                    @endforeach
                </x-slot:dropdown>
            </x-dropdown.dropdown>

            <x-forms.input
                class:container="hidden"
                type="select"
                name="template_aspect_ratio"
                x-model="generateAiReferenceAspectRatio"
            >
                @foreach ($aspect_ratios as $aspect_ratio)
                    <option value="{{ $aspect_ratio['value'] }}">{{ $aspect_ratio['label'] }}</option>
                @endforeach
            </x-forms.input>
        </div>

        <x-button
            class="inline-grid size-11 shrink-0 rounded-full bg-black p-0 text-white hover:bg-primary hover:text-primary-foreground disabled:opacity-50 dark:bg-white dark:text-black"
            type="submit"
            ::disabled="generatingTemplate"
            title="{{ __('Generate') }}"
        >
            <svg
                class="col-start-1 col-end-1 row-start-1 row-end-1"
                width="15"
                height="12"
                viewBox="0 0 15 12"
                fill="currentColor"
                xmlns="http://www.w3.org/2000/svg"
                x-show="!generatingTemplate"
            >
                <path d="M0 12V7.5L6 6L0 4.5V0L14.25 6L0 12Z" />
            </svg>
            <x-tabler-loader-2
                class="col-start-1 col-end-1 row-start-1 row-end-1 size-5 animate-spin"
                x-show="generatingTemplate"
                x-cloak
            />
        </x-button>
    </div>
</form>

@include('creative-suite-ai-template::partials.template-form-scripts')
