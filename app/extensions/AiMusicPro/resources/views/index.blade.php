@php
    $ai_music_options = [
        'duration' => [
            '' => __('None'),
            '30' => '30 ' . __('seconds'),
            '60' => '1 ' . __('minute'),
            '120' => '2 ' . __('minutes'),
            '180' => '3 ' . __('minutes'),
        ],
        'music_style' => [
            '' => __('None'),
            'techno' => 'Techno',
            'lofi' => 'Lo-Fi',
            'pop' => 'Pop',
            'jazz' => 'Jazz',
            'rock' => 'Rock',
            'classical' => 'Classical',
            'hiphop' => 'Hip-Hop/Trap',
            'edm' => 'EDM/House',
            'reggae' => 'Reggae',
            'ambient' => 'Ambient',
            'cozy' => 'Cozy',
            'relaxed' => 'Relaxed',
        ],
    ];
    $lyricsPlaceholder = "Verse 1\nWalking down the road at dawn\nFeeling like the world moves on\n\nChorus\nBut I keep holding on...";
    $lyricsTooltip = __('Add custom lyrics with structural tags like Verse, Chorus, Bridge, Intro, Outro. You can also use timestamps for timing control.');
@endphp

@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('AI Music Pro'))
@section('titlebar_subtitle', __('Create music tracks with AI'))

@section('content')
    <div class="py-10">
        <x-card
            class="lqd-voiceover-generator mb-16 bg-[#F2F1FD] shadow-sm dark:bg-foreground/5"
            class:body="pt-3"
            size="lg"
            x-data="{
                provider: '{{ $provider }}',
                prompts: [
                    '{{ addslashes(__('Dreamy lo-fi hip hop beat for studying, with soft piano and vinyl crackle.')) }}',
                    '{{ addslashes(__('Epic orchestral soundtrack with choirs and cinematic percussion.')) }}',
                    '{{ addslashes(__('Futuristic synthwave track inspired by neon cityscapes.')) }}',
                    '{{ addslashes(__('Gentle acoustic guitar melody with birds chirping in the background.')) }}',
                    '{{ addslashes(__('Dark techno track with deep bass and hypnotic rhythms.')) }}',
                    '{{ addslashes(__('Upbeat funk groove with slap bass, brass, and electric guitar.')) }}',
                    '{{ addslashes(__('Meditative ambient soundscape with evolving pads and nature sounds.')) }}',
                    '{{ addslashes(__('Fast-paced video game chiptune with retro 8-bit sounds.')) }}',
                    '{{ addslashes(__('Traditional Japanese koto and shakuhachi with modern electronic fusion.')) }}',
                    '{{ addslashes(__('Joyful children\'s song with ukulele, handclaps, and playful whistles.')) }}',
                ],
                generateRandomPrompt() {
                    return this.prompts[Math.floor(Math.random() * this.prompts.length)];
                },
                prompt: '',
                imageFiles: [],
                imagePreviews: [],
                addImages(event) {
                    const files = Array.from(event.target.files);
                    const remaining = 10 - this.imageFiles.length;
                    files.slice(0, remaining).forEach(file => {
                        this.imageFiles.push(file);
                        this.imagePreviews.push(URL.createObjectURL(file));
                    });
                    event.target.value = '';
                },
                removeImage(index) {
                    URL.revokeObjectURL(this.imagePreviews[index]);
                    this.imageFiles.splice(index, 1);
                    this.imagePreviews.splice(index, 1);
                }
            }"
        >
            <form
                class="workbook-form flex flex-col"
                id="ai_music_generator_form"
                onsubmit="return generateMusic();"
                x-data="{ advancedSettingsShow: false }"
            >
                <x-forms.input
                    class="rounded-none border-e-0 border-s-0 border-t-0 border-foreground/10 bg-transparent px-0 py-7 font-serif text-xl focus:outline-none focus:ring-0"
                    id="workbook_title"
                    placeholder="{{ __('Untitled Song...') }}"
                    required
                />

                <hr class="border-foreground/10" />

                <h5 class="flex w-full flex-wrap items-center gap-2 py-3">
                    {{ __('Describe your song') }}
                    <button
                        class="cursor-pointer text-foreground/70 underline hover:text-foreground"
                        type="button"
                        @click="prompt = generateRandomPrompt()"
                    >
                        {{ __('Generate example prompt') }}
                    </button>
                </h5>

                <x-forms.input
                    class="w-full bg-background placeholder:text-foreground/50"
                    id="ai_music_prompt"
                    size="lg"
                    type="textarea"
                    name="ai_music_prompt"
                    cols="30"
                    rows="3"
                    ::value="prompt"
                    ::placeholder="generateRandomPrompt()"
                ></x-forms.input>

                <x-button
                    class="mt-4 self-start text-3xs font-semibold text-heading-foreground"
                    tag="button"
                    type="button"
                    variant="link"
                    @click="advancedSettingsShow = !advancedSettingsShow"
                >
                    <span class="inline-flex size-9 items-center justify-center rounded-full bg-background shadow-sm">
                        <x-tabler-plus
                            class="size-4"
                            x-show="!advancedSettingsShow"
                        />
                        <x-tabler-minus
                            class="size-4"
                            x-cloak
                            x-show="advancedSettingsShow"
                        />
                    </span>
                    {{ __('Advanced Settings') }}
                </x-button>

                <div
                    class="mt-3 flex w-full flex-wrap justify-between gap-5"
                    x-show="advancedSettingsShow"
                    x-cloak
                    x-collapse
                >
                    {{-- ElevenLabs: Duration selector --}}
                    <div
                        class="grow"
                        x-show="provider === 'elevenlabs'"
                        x-cloak
                    >
                        <x-forms.input
                            id="duration"
                            container-class="grow"
                            label="{{ __('Music Duration') }}"
                            type="select"
                            name="duration"
                            size="lg"
                        >
                            @foreach ($ai_music_options['duration'] as $value => $label)
                                <option value="{{ $value }}">{{ __($label) }}</option>
                            @endforeach
                        </x-forms.input>
                    </div>

                    {{-- Lyria Clip: Duration info --}}
                    <div
                        class="w-full grow"
                        x-show="provider === 'lyria3clip'"
                        x-cloak
                    >
                        <x-alert
                            variant="info"
                            size="sm"
                        >
                            {{ __('Lyria 3 Clip generates ~30 second music clips.') }}
                        </x-alert>
                    </div>

                    {{-- Lyria Pro: Duration info --}}
                    <div
                        class="w-full grow"
                        x-show="provider === 'lyria3pro'"
                        x-cloak
                    >
                        <x-alert
                            variant="info"
                            size="sm"
                        >
                            {{ __('Lyria 3 Pro generates full songs (~2 minutes). You can control duration via your prompt (e.g. "Create a 1-minute song") or by using timestamps in the lyrics field.') }}
                        </x-alert>
                    </div>

                    <x-forms.input
                        id="music_style"
                        label="{{ __('Music Style') }}"
                        name="music_style"
                        container-class="grow"
                        size="lg"
                        type="select"
                    >
                        @foreach ($ai_music_options['music_style'] as $value => $label)
                            <option value="{{ $value }}">{{ __($label) }}</option>
                        @endforeach
                    </x-forms.input>

                    {{-- Lyria: Custom Lyrics --}}
                    <div
                        class="w-full"
                        x-show="provider !== 'elevenlabs'"
                        x-cloak
                    >
                        <x-forms.input
                            class="w-full bg-background font-mono text-xs placeholder:text-foreground/50"
                            id="lyrics"
                            name="lyrics"
                            label="{{ __('Custom Lyrics') }}"
                            type="textarea"
                            size="lg"
                            rows="5"
                            placeholder="{{ $lyricsPlaceholder }}"
                            tooltip="{{ $lyricsTooltip }}"
                        ></x-forms.input>
                    </div>

                    {{-- Lyria: Image Inspiration --}}
                    <div
                        class="w-full"
                        x-show="provider !== 'elevenlabs'"
                        x-cloak
                    >
                        <label class="lqd-input-label mb-3 flex cursor-pointer items-center gap-2 text-2xs font-medium leading-none text-label">
                            {{ __('Inspiration Images') }}
                            <x-info-tooltip text="{{ __('Upload up to 10 images to inspire the music composition. The AI will use the mood and atmosphere of the images.') }}" />
                        </label>
                        <div class="flex flex-wrap items-center gap-2">
                            <template
                                x-for="(preview, index) in imagePreviews"
                                :key="index"
                            >
                                <div class="relative size-16 overflow-hidden rounded-lg border border-input-border">
                                    <img
                                        class="size-full object-cover"
                                        :src="preview"
                                    />
                                    <button
                                        class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity hover:opacity-100"
                                        type="button"
                                        @click="removeImage(index)"
                                    >
                                        <x-tabler-x class="size-4 text-white" />
                                    </button>
                                </div>
                            </template>
                            <label
                                class="flex size-16 cursor-pointer items-center justify-center rounded-lg border border-dashed border-input-border bg-input-background transition-colors hover:border-primary"
                                x-show="imageFiles.length < 10"
                            >
                                <x-tabler-plus class="size-5 text-foreground/50" />
                                <input
                                    class="hidden"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    @change="addImages($event)"
                                />
                            </label>
                        </div>
                        <p
                            class="mt-1 text-2xs text-foreground/50"
                            x-show="imageFiles.length > 0"
                        >
                            <span x-text="imageFiles.length"></span>/10 {{ __('images') }}
                        </p>
                    </div>

                    {{-- Lyria Pro: Output Format --}}
                    <div
                        class="grow"
                        x-show="provider === 'lyria3pro'"
                        x-cloak
                    >
                        <x-forms.input
                            id="output_format"
                            container-class="grow"
                            label="{{ __('Output Format') }}"
                            type="select"
                            name="output_format"
                            size="lg"
                        >
                            <option value="mp3">MP3</option>
                            <option value="wav">WAV ({{ __('Higher quality') }})</option>
                        </x-forms.input>
                    </div>
                </div>

                <hr class="mt-6 border-foreground/10" />

                <div class="flex pt-3">
                    <x-button
                        class="w-full py-2.5"
                        id="generate_ai_music_button"
                        tag="button"
                        type="{{ $app_is_demo ? 'button' : 'submit' }}"
                        size="lg"
                        onclick="{{ $app_is_demo ? 'return toastr.info(\'This feature is disabled in Demo version.\')' : '' }}"
                    >
                        <x-tabler-plus class="size-5" />
                        {{ __('Generate') }}
                    </x-button>
                </div>
            </form>
        </x-card>

		<div
			x-data
			x-init="
				$nextTick(() => {
					const durationEl = document.getElementById('duration');
					const getQuantity = () => {
						const val = parseInt(durationEl?.value);
						return val > 0 ? val / 60 : 1;
					};
					$dispatch('generator-changed', { generator: '{{ \App\Domains\Entity\Enums\EntityEnum::ELEVENLABS_AI_MUSIC->value }}', quantity: getQuantity(), _force: Date.now() });
					durationEl?.addEventListener('change', () => {
						$dispatch('generator-changed', { generator: '{{ \App\Domains\Entity\Enums\EntityEnum::ELEVENLABS_AI_MUSIC->value }}', quantity: getQuantity(), _force: Date.now() });
					});
				});
			"
		>
			<x-cost-preview class="w-full justify-end" />
		</div>

        <div id="generator_sidebar_table">
            @include('ai-music-pro::components.generator_sidebar_table', ['music' => $music])
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ custom_theme_url('/assets/libs/wavesurfer/wavesurfer.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/js/panel/voiceover.js') }}"></script>

    <script>
        function generateMusic(ev) {
            ev?.preventDefault();
            ev?.stopPropagation();

            const generateBtn = document.querySelector('#generate_ai_music_button');
            if (generateBtn) {
                generateBtn.disabled = true;
                generateBtn.innerHTML = "{{ __('Please wait...') }}";
            }

            // Show loading indicator if Alpine store exists
            if (typeof Alpine !== 'undefined' && Alpine.store('appLoadingIndicator')) {
                Alpine.store('appLoadingIndicator').show();
            }

            const formData = new FormData();
            const alpineData = Alpine.$data(document.querySelector('.lqd-voiceover-generator'));
            formData.append('workbook_title', document.getElementById('workbook_title').value);
            formData.append('ai_music_prompt', document.getElementById('ai_music_prompt').value);
            formData.append('music_style', document.getElementById('music_style').value);

            if (alpineData.provider === 'elevenlabs') {
                formData.append('duration', document.getElementById('duration').value);
            }

            if (alpineData.provider !== 'elevenlabs') {
                const lyricsEl = document.getElementById('lyrics');
                if (lyricsEl && lyricsEl.value) {
                    formData.append('lyrics', lyricsEl.value);
                }

                const outputFormatEl = document.getElementById('output_format');
                if (outputFormatEl) {
                    formData.append('output_format', outputFormatEl.value);
                }

                alpineData.imageFiles.forEach((file, i) => {
                    formData.append('images[' + i + ']', file);
                });
            }

            $.ajax({
                type: "post",
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                url: "/dashboard/user/ai-music-pro/generate",
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    if (res.status !== 'success') {
                        if (typeof toastr !== 'undefined') {
                            toastr.error(res.message ?? "{{ __('Something went wrong') }}");
                        }
                    } else {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(res.message);
                        }

                        $("#generator_sidebar_table").html(res?.html2);
                        const audioElements = document.querySelectorAll('.data-audio');
                        if (audioElements.length) {
                            audioElements.forEach(generateWaveForm);
                        }
                    }

                    resetGenerateButton(generateBtn);

                    if (res.status === 'success') {
                        window.dispatchEvent(new CustomEvent('lqd:credit-balance-refresh'));
                    }
                },
                error: function(data) {
                    resetGenerateButton(generateBtn);

                    if (typeof toastr !== 'undefined') {
                        if (data.responseJSON?.errors) {
                            $.each(data.responseJSON.errors, function(index, value) {
                                toastr.error(value);
                            });
                        } else if (data.responseJSON?.message) {
                            toastr.error(data.responseJSON.message);
                        } else {
                            toastr.error("Something went wrong");
                        }
                    }
                }
            });

            return false;
        }

        function resetGenerateButton(generateBtn) {
            if (generateBtn) {
                generateBtn.disabled = false;
                generateBtn.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg> {{ __('Generate') }}';
            }

            // Hide loading indicator if Alpine store exists
            if (typeof Alpine !== 'undefined' && Alpine.store('appLoadingIndicator')) {
                Alpine.store('appLoadingIndicator').hide();
            }
        }
    </script>
@endpush
