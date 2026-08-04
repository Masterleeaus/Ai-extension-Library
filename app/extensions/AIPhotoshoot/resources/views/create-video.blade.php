@php
    $apsMaxUploadMb = \App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry::getMaxUploadSizeMb();
@endphp
@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Create Video'))
@section('titlebar_pretitle', '')
@section('titlebar_actions')
    <x-button
        href="{{ route('dashboard.user.ai-photoshoot.photo_shoots.my') }}"
        variant="ghost-shadow"
    >
        {{ __('My Photoshoots') }}
    </x-button>

    <x-button
        href="{{ route('dashboard.user.ai-photoshoot.index') }}"
        variant="primary"
    >
        <x-tabler-plus class="size-4" />
        {{ __('New Photoshoot') }}
    </x-button>
@endsection
@section('titlebar_subtitle', __('Transform your image into a stunning video.'))

@section('content')
    <div
        class="py-10"
        x-data="apsCreateVideoApp()"
    >
        <div class="flex flex-col gap-4 lg:flex-row lg:gap-9">
            {{-- Left Panel: Upload Image and Prompt --}}
            <div class="w-full lg:basis-1/3">
                <p class="mb-5 border-b py-2.5 text-[12px] font-semibold transition-border">
                    {{ __('Create a Video') }}
                </p>

                <form
                    class="space-y-4 sm:space-y-6"
                    id="aps-create-video-form"
                    @submit.prevent="generateVideo"
                >
                    @csrf

                    {{-- Start Image --}}
                    <div>
                        <label class="mb-4 flex items-center gap-1 text-2xs font-medium">
                            {{ __('Start Image') }}
                            <x-info-tooltip
                                class:content="-start-4 translate-x-0 text-2xs"
                                text="{{ __('Upload the image you want to transform into a video.') }}"
                            />
                        </label>

                        <input
                            class="hidden"
                            type="file"
                            x-ref="imageInput"
                            accept="image/*"
                            @change="handleFileSelect($event)"
                        >

                        <div
                            class="cursor-pointer rounded-[10px] border border-dashed border-foreground/10 p-6 text-center transition hover:border-primary hover:bg-primary/5 sm:p-8"
                            @click="$refs.imageInput.click()"
                            @dragover.prevent="dragOver = true"
                            @dragleave.prevent="dragOver = false"
                            @drop.prevent="handleFileDrop($event)"
                            :class="{ 'drag-over': dragOver }"
                        >
                            <div x-show="!imagePreview">
                                <p class="mb-2 text-sm font-medium">
                                    {{ __('Drag and drop or click to browse') }}
                                </p>
                                <p class="m-0 text-4xs font-medium opacity-50">
                                    {{ __('Max File Size: :size MB', ['size' => $apsMaxUploadMb]) }}
                                </p>
                            </div>

                            <div
                                x-show="imagePreview"
                                x-cloak
                            >
                                <div class="relative mx-auto mb-2 inline-block w-32">
                                    <img
                                        class="max-h-40 w-full rounded-lg border object-cover"
                                        :src="imagePreview"
                                    >
                                    <button
                                        class="absolute -end-4 -top-4 inline-grid size-8 place-items-center rounded-full bg-background text-foreground shadow-lg shadow-black/5 transition hover:scale-110 hover:bg-red-500 hover:text-white"
                                        type="button"
                                        @click.stop="resetFile()"
                                        title="{{ __('Remove Image') }}"
                                    >
                                        <x-tabler-x class="size-4" />
                                    </button>
                                </div>
                                <p
                                    class="m-0 text-4xs font-medium opacity-50"
                                    x-text="imageFileName"
                                ></p>
                            </div>
                        </div>
                    </div>

                    {{-- Video Description/Prompt --}}
                    <x-forms.input
                        class:label="text-heading-foreground text-2xs font-medium"
                        label="{{ __('Describe your video') }}"
                        tooltip="{{ __('Describe what you want the video to look like or what motion to apply.') }}"
                        type="textarea"
                        x-model="videoPrompt"
                        rows="4"
                        placeholder="{{ __('Describe the motion or transformation you want to see...') }}"
                    />

                    <x-button
                        class="w-full"
                        id="aps-generate-video-btn"
                        size="xl"
                        type="submit"
                        variant="primary"
                        disabled
                        ::disabled="generating || !imageFile"
                    >
                        {{ __('Generate Video') }}
                    </x-button>
                </form>
            </div>

            {{-- Right Panel: Results --}}
            <div class="flex w-full flex-col lg:basis-2/3">
                <div class="mb-5 flex flex-wrap justify-between gap-3 border-b py-2.5 transition-border">
                    <p class="m-0 text-[12px] font-semibold">
                        {{ __('Generated Results') }}
                    </p>

                    <x-button
                        class="text-[12px] font-semibold"
                        variant="link"
                        @click="downloadAll"
                        x-show="results.length"
                        x-cloak
                    >
                        {{ __('Download All') }}
                        <x-tabler-download class="size-4" />
                    </x-button>
                </div>

                {{-- Empty State --}}
                <div
                    class="grid min-h-[250px] grow place-items-center rounded-[10px] border border-dashed border-foreground/10 p-8 text-center"
                    x-show="!results.length && !generating"
                >
                    <p class="text-sm font-medium">
                        {{ __('Your video will appear here.') }}
                    </p>
                </div>

                <div
                    class="lqd-loading-skeleton lqd-is-loading grid grid-cols-1 gap-4 sm:grid-cols-2"
                    x-cloak
                    x-show="generating"
                >
                    <div
                        class="aspect-[3/4] w-full rounded"
                        data-lqd-skeleton-el
                    ></div>
                    <div
                        class="aspect-[3/4] w-full rounded"
                        data-lqd-skeleton-el
                    ></div>
                </div>

                {{-- Results Grid --}}
                <div
                    class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2"
                    x-show="results.length"
                    x-cloak
                >
                    <template
                        x-for="(result, index) in results"
                        :key="index"
                    >
                        <div class="group relative">
                            <video
                                class="w-full"
                                :src="result.video_url || result.image_url"
                                controls
                            ></video>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        const APS_CREATE_VIDEO_ROUTES = {
            generate: '{{ route('dashboard.user.ai-photoshoot.create_video.generate') }}',
            checkStatus: '{{ url('dashboard/user/ai-photoshoot/create_video/status') }}',
        };

        function apsCreateVideoApp() {
            return {
                imageFile: null,
                imagePreview: null,
                imageFileName: null,
                dragOver: false,

                videoPrompt: '',

                generating: false,
                generationComplete: false,
                results: [],

                init() {
                    const createVideoData = sessionStorage.getItem('apsCreateVideoData');
                    if (createVideoData) {
                        try {
                            const data = JSON.parse(createVideoData);
                            this.loadImageFromUrl(data.url, data.fileName);
                            sessionStorage.removeItem('apsCreateVideoData');
                        } catch (error) {
                            console.error('Failed to load pre-selected image:', error);
                        }
                    }
                },

                async loadImageFromUrl(url, fileName) {
                    try {
                        const response = await fetch(url);
                        const blob = await response.blob();
                        const file = new File([blob], fileName, {
                            type: blob.type
                        });

                        this.imageFile = file;
                        this.imagePreview = url;
                        this.imageFileName = fileName;
                    } catch (error) {
                        console.error('Failed to fetch image:', error);
                        window.toastr?.error("{{ __('Failed to load selected image') }}");
                    }
                },

                handleFileSelect(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.processFile(file);
                    }
                },

                handleFileDrop(event) {
                    this.dragOver = false;
                    const file = event.dataTransfer.files[0];
                    if (file) {
                        this.processFile(file);
                    }
                },

                processFile(file) {
                    if (!file.type.startsWith('image/')) {
                        window.toastr?.error("{{ __('Please upload an image file') }}");
                        return;
                    }

                    if (file.size > {{ $apsMaxUploadMb }} * 1024 * 1024) {
                        window.toastr?.error("{{ __('File size must be less than :size MB', ['size' => $apsMaxUploadMb]) }}");
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.imageFile = file;
                        this.imagePreview = e.target.result;
                        this.imageFileName = file.name;
                    };
                    reader.readAsDataURL(file);
                },

                resetFile() {
                    this.imageFile = null;
                    this.imagePreview = null;
                    this.imageFileName = null;
                    if (this.$refs.imageInput) {
                        this.$refs.imageInput.value = '';
                    }
                },

                async generateVideo() {
                    if (!this.imageFile) {
                        window.toastr?.warning("{{ __('Please upload a start image') }}");
                        return;
                    }

                    this.generating = true;
                    this.generationComplete = false;

                    const formData = new FormData();
                    formData.append('image', this.imageFile);
                    formData.append('prompt', this.videoPrompt);
                    formData.append('_token', document.querySelector('input[name="_token"]').value);

                    try {
                        const response = await fetch(APS_CREATE_VIDEO_ROUTES.generate, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.checkGenerationStatus(data.id);
                        } else {
                            throw new Error(data.message || "{{ __('Generation failed') }}");
                        }
                    } catch (error) {
                        console.error('Generation error:', error);
                        window.toastr?.error(error.message || "{{ __('Failed to create video') }}");
                        this.generating = false;
                    }
                },

                checkGenerationStatus(id) {
                    let isCompleted = false;

                    const checkInterval = setInterval(async () => {
                        if (isCompleted) return;

                        try {
                            const response = await fetch(`${APS_CREATE_VIDEO_ROUTES.checkStatus}/${id}`, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            });

                            const data = await response.json();

                            if (isCompleted) return;

                            const status = data.status?.toLowerCase();

                            if (status === 'completed') {
                                isCompleted = true;
                                clearInterval(checkInterval);
                                this.generating = false;
                                this.generationComplete = true;
                                window.toastr?.success("{{ __('Video created successfully!') }}");
                                this.results = data.results || [];
                            } else if (status === 'failed') {
                                isCompleted = true;
                                clearInterval(checkInterval);
                                this.generating = false;
                                window.toastr?.error("{{ __('Generation failed. Please try again.') }}");
                            }
                        } catch (error) {
                            isCompleted = true;
                            clearInterval(checkInterval);
                            console.error('Status check error:', error);
                            this.generating = false;
                            window.toastr?.error("{{ __('Failed to check generation status') }}");
                        }
                    }, 3000);

                    setTimeout(() => {
                        if (isCompleted) return;
                        isCompleted = true;
                        clearInterval(checkInterval);
                        if (this.generating) {
                            this.generating = false;
                            window.toastr?.error("{{ __('Generation timeout. Please try again.') }}");
                        }
                    }, 360000);
                },

                downloadAll() {
                    this.results.forEach((result, index) => {
                        const link = document.createElement('a');
                        link.href = result.video_url || result.image_url;
                        link.download = `aps-video-${index + 1}.mp4`;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    });
                }
            };
        }
    </script>
@endpush
