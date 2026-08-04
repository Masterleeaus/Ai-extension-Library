<x-card
    class="lqd-voiceover-generator mb-8 bg-[#F2F1FD] shadow-sm dark:bg-foreground/5"
    class:body="pt-3"
    size="lg"
>
    <form
        class="workbook-form flex flex-col gap-8"
        id="openai_generator_form"
        
    >

        <div
            class="flex w-full flex-col gap-5"
            ondrop="dropHandler(event, 'file');"
            ondragover="dragOverHandler(event);"
        >
            <label
                class="lqd-filepicker-label mt-5 flex min-h-64 w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-foreground/10 bg-background text-center transition-colors hover:bg-background/80"
                for="file"
            >
                <div class="flex flex-col items-center justify-center py-6">
                    <x-tabler-volume
                        class="mx-auto mb-2 size-8"
                        stroke-width="1.5"
                    />
                    <p class="m-0 font-medium">
                        <span class="opacity-70">
                            @lang('Drag and drop an audio file')
                        </span>
                    </p>
                    <p class="file-name mb-0 text-2xs">
                        @lang('or click here to browse your files.')
                    </p>
                </div>

                @foreach (json_decode($openai->questions) ?? [] as $question)
                    <x-forms.input
                        class="hidden"
                        class:label="static text-center text-heading-foreground/40 text-3xs lg:px-10 before:absolute before:top-0 before:start-0 before:w-full before:h-full before:block before:z-2"
                        id="file"
                        container-class="static"
                        size="lg"
                        label="{{ __($question->question) }}"
                        name="{{ $question->name }}"
                        type="file"
                        placeholder="{{ __($question->question) }}"
                        accept="audio/*"
                        onchange="handleFileSelect('file')"
                    />
                @endforeach
            </label>
        </div>

        <div class="flex w-full">
            <x-button
                class="z-50 w-full py-2.5"
                id="openai_generator_button"
                tag="button"
                type="submit"
                size="lg"
                form="openai_generator_form"
            >
                <x-tabler-plus class="size-5" />
                {{ __('Isolate Voice') }}
            </x-button>
        </div>
    </form>
</x-card>

<div
    x-data
    x-init="$nextTick(() => $dispatch('generator-changed', { generator: '{{ \App\Domains\Entity\Enums\EntityEnum::ISOLATOR->value }}', _force: Date.now() }))"
>
    <x-cost-preview class="w-full justify-end" />
</div>

<h2 class="font-bold">
    @lang('Audio Files')
</h2>
<x-card
    class="w-full [&_.tox-edit-area__iframe]:!bg-transparent"
    id="generator_sidebar_table"
    variant="{{ Theme::getSetting('defaultVariations.card.variant', 'outline') === 'outline' ? 'none' : Theme::getSetting('defaultVariations.card.variant', 'solid') }}"
    size="{{ Theme::getSetting('defaultVariations.card.variant', 'outline') === 'outline' ? 'none' : Theme::getSetting('defaultVariations.card.size', 'md') }}"
    roundness="{{ Theme::getSetting('defaultVariations.card.roundness', 'default') === 'default' ? 'none' : Theme::getSetting('defaultVariations.card.roundness', 'default') }}"
>
    @include('panel.user.openai.components.generator_sidebar_table')
</x-card>

@push('script')
    <script>
        function dropHandler(ev, id) {
            // Prevent default behavior (Prevent file from being opened)
            ev.preventDefault();
            const file = ev.dataTransfer.files?.[0];
            if (!validateVoiceFile(file)) return;
            $('#' + id)[0].files = ev.dataTransfer.files;
            $('#' + id).closest('label').find(".file-name").text(file.name);
        }

        function validateVoiceFile(file) {
            if (!file) return false;
            const maxBytes = {{ (int) config('voice-isolator.max_upload_bytes', 52428800) }};
            const allowed = @json(config('voice-isolator.allowed_mime_types', ['audio/mpeg','audio/wav','audio/x-wav','audio/mp4','audio/ogg','audio/webm']));
            if (file.size > maxBytes || !allowed.includes(file.type)) {
                alert(@json(__('Please choose a supported audio file within the upload limit.')));
                return false;
            }
            return true;
        }

        let voiceIsolationSubmitting = false;
        document.getElementById('openai_generator_form')?.addEventListener('submit', function (event) {
            event.preventDefault();
            if (voiceIsolationSubmitting) return;
            const file = document.getElementById('file')?.files?.[0];
            if (!validateVoiceFile(file)) return;
            voiceIsolationSubmitting = true;
            const submitButton = this.querySelector('[type=submit]');
            if (submitButton) submitButton.disabled = true;
            try {
                sendOpenaiGeneratorForm();
            } finally {
                window.setTimeout(() => { voiceIsolationSubmitting = false; if (submitButton) submitButton.disabled = false; }, {{ (int) config('voice-isolator.submit_guard_milliseconds', 3000) }});
            }
        });

        function dragOverHandler(ev) {
            // Prevent default behavior (Prevent file from being opened)
            ev.preventDefault();
        }

        function handleFileSelect(id) {
            const $el = $('#' + id);
            const file = $el[0].files[0];

            if (!validateVoiceFile(file)) { $el.val(''); return; }
            $el.closest('label').find(".file-name").text(file?.name ?? '');
        }
    </script>
@endpush
