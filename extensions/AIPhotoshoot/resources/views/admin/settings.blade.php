@extends('panel.layout.settings', ['layout' => 'wide'])
@section('title', __('AI Photoshoot Settings'))
@section('titlebar_actions', '')
@section('titlebar_subtitle', __('Configure AI Photoshoot generation settings.'))

@section('settings')
    <form
        method="post"
        action="{{ route('dashboard.admin.ai-photoshoot.settings.update') }}"
        id="settings_form"
        enctype="multipart/form-data"
        x-data='{
            selectedImageModel: @json($currentImageModel),
            openAIModels: @json($openAIModelValues),
            falModels: @json($falModelValues),
            get isOpenAI() { return this.openAIModels.includes(this.selectedImageModel); },
            get isFal() { return this.falModels.includes(this.selectedImageModel); }
        }'
    >
        @csrf
        <h3 class="mb-[25px] text-[20px]">{{ __('Image Generation Settings') }}</h3>
        <div class="row">
            <x-card
                class="mb-3 max-md:text-center"
                size="lg"
            >
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Default Image Model') }}</label>
                        <select
                            class="form-select"
                            id="ai-photoshoot-image-default-model"
                            name="ai-photoshoot-image-default-model"
                            x-model="selectedImageModel"
                        >
                            @foreach ($imageModels as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected($currentImageModel === $value && ($imageModelEnabled[$value] ?? true))
                                    @disabled(! ($imageModelEnabled[$value] ?? true))
                                >{{ $label }}{{ ($imageModelEnabled[$value] ?? true) ? '' : ' — ' . __('disabled in API integration settings') }}</option>
                            @endforeach
                        </select>
                        <x-alert class="mt-2">
                            <p>
                                {{ __('Select the default AI model used for image generation and editing in AI Photoshoot. Models from FAL (nano-banana family) and OpenAI (gpt-image family) are supported.') }}
                            </p>
                        </x-alert>
                    </div>
                </div>
            </x-card>
        </div>
        <div x-show="isFal" x-cloak>
        <h3 class="mb-[25px] text-[20px]">{{ __('Nano Banana Settings') }}</h3>
        <div class="row">
            <x-card
                class="mb-3 max-md:text-center"
                size="lg"
            >
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Default Aspect Ratio') }}</label>
                        <select
                            class="form-select"
                            id="ai-photoshoot-fal-ratio"
                            name="ai-photoshoot-fal-ratio"
                        >
                            @foreach ($falRatioOptions as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected($currentFalRatio === $value)
                                >{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Default Resolution') }}</label>
                        <select
                            class="form-select"
                            id="ai-photoshoot-fal-resolution"
                            name="ai-photoshoot-fal-resolution"
                        >
                            @foreach ($falResolutionOptions as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected($currentFalResolution === $value)
                                >{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-alert class="mt-2">
                        <p>
                            {{ __('Default aspect ratio and resolution applied when users do not pick their own values. Lower resolution (1K) is the cheapest. Used by Nano Banana Pro and any other FAL-based image models.') }}
                        </p>
                    </x-alert>
                </div>
            </x-card>
        </div>
        </div>
        <div x-show="isOpenAI" x-cloak>
        <h3 class="mb-[25px] text-[20px]">{{ __('OpenAI Image Settings') }}</h3>
        <div class="row">
            <x-card
                class="mb-3 max-md:text-center"
                size="lg"
            >
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Quality') }}</label>
                        <select
                            class="form-select"
                            id="ai-photoshoot-openai-quality"
                            name="ai-photoshoot-openai-quality"
                        >
                            @foreach ($qualityOptions as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected($currentQuality === $value)
                                >{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Size') }}</label>
                        <select
                            class="form-select"
                            id="ai-photoshoot-openai-size"
                            name="ai-photoshoot-openai-size"
                        >
                            @foreach ($sizeOptions as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected($currentSize === $value)
                                >{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-alert class="mt-2">
                        <p>
                            {{ __('Lower quality and smaller size reduce per-image cost. \'Low\' quality at 1024x1024 is the cheapest.') }}
                        </p>
                        <p>
                            {{ __('Larger sizes (2048+, 3840) only apply to GPT Image 2.') }}
                        </p>
                    </x-alert>
                </div>
            </x-card>
        </div>
        </div>
        <h3 class="mb-[25px] text-[20px]">{{ __('Upload Settings') }}</h3>
        <div class="row">
            <x-card
                class="mb-3 max-md:text-center"
                size="lg"
            >
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Max Upload Size (MB)') }}</label>
                        <input
                            class="form-control"
                            id="ai-photoshoot-max-upload-size-mb"
                            name="ai-photoshoot-max-upload-size-mb"
                            type="number"
                            min="1"
                            max="100"
                            step="1"
                            value="{{ $currentMaxUploadSizeMb }}"
                        />
                        <x-alert class="mt-2">
                            <p>
                                {{ __('Maximum file size (in megabytes) allowed for product and image uploads in custom, template, replace background, edit, and create-video flows. Default is 5 MB.') }}
                            </p>
                        </x-alert>
                    </div>
                </div>
            </x-card>
        </div>
        <h3 class="mb-[25px] text-[20px]">{{ __('Video Generation Settings') }}</h3>
        <div class="row">
            <x-card
                class="mb-3 max-md:text-center"
                size="lg"
            >
                <div class="col-md-12 space-y-4">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Image-to-Video Model') }}</label>
                        <select
                            class="form-select"
                            id="ai-photoshoot-video-default-model"
                            name="ai-photoshoot-video-default-model"
                        >
                            @foreach ($videoModels as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected($currentVideoModel === $value)
                                >{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-alert class="mt-2">
                            <p>
                                {{ __('Model used when users turn a generated photo into a video.') }}
                            </p>
                        </x-alert>
                    </div>
                </div>
            </x-card>
        </div>
        <button
            class="btn btn-primary w-full"
            type="submit"
        >
            {{ __('Save') }}
        </button>
    </form>
@endsection

@push('script')
    <script src="{{ custom_theme_url('/assets/js/panel/settings.js') }}"></script>
@endpush
