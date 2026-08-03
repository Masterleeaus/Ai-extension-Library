@push('css')
    <link
        rel="stylesheet"
        href="{{ custom_theme_url('/assets/libs/cropperjs/cropper.min.css') }}"
    />
    <style>
        .cropper-container {
            min-height: 100%;
        }
    </style>
@endpush

<div
    class="bg-background py-8"
    x-cloak
    x-data="latestPhotoshootsComponent"
    @keyup.escape.window="isCropMode ? exitCropMode() : (modalShow = false)"
    @keyup.arrow-left.window="modalShow && !isCropMode && prevItem()"
    @keyup.arrow-right.window="modalShow && !isCropMode && nextItem()"
>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <h3 class="m-0">
            {{ __('Latest Photoshoots') }}
        </h3>

        <x-button
            href="{{ route('dashboard.user.ai-photoshoot.photo_shoots.my') }}"
            variant="link"
        >
            {{ __('View All') }}
            <x-tabler-chevron-right class="size-4 transition group-hover:translate-x-1 rtl:rotate-180 rtl:group-hover:-translate-x-1" />
        </x-button>
    </div>

    <p
        class="flex items-center gap-1"
        x-show="loading"
    >
        <x-tabler-loader-2 class="size-4 animate-spin" />
        {{ __('Loading images') }}
    </p>

    {{-- Empty State --}}
    <div
        x-show="!loading && images.length === 0"
        x-cloak
    >
        <p class="mb-4 opacity-60">
            {{ __('No photoshoots yet. Start creating your first photoshoot.') }}
        </p>
    </div>

    {{-- Gallery Grid --}}
    <div class="grid grid-cols-2 gap-1 empty:hidden md:grid-cols-3 lg:grid-cols-4">
        <template
            x-for="image in images"
            :key="image.id"
        >
            <div
                class="image-result group relative aspect-square cursor-pointer break-inside-avoid"
                :data-id="image.id"
            >
                <div class="relative size-full overflow-hidden">
                    <img
                        class="size-full object-cover object-center"
                        :src="`${(image.thumbnail || image.url).startsWith('upload') ? '/' : ''}${image.thumbnail || image.url}`"
                        :alt="image.title || image.input"
                        loading="lazy"
                        @click="setActiveItem({...image, output: image.url}); modalShow = true"
                    >

                    {{-- TODO: make these dynamically generated --}}
                    <template x-if="image.variations">
                        <span class="absolute start-3 top-3 inline-block rounded-full bg-background px-3.5 py-2 text-xs font-medium text-heading-foreground">
                            {{ __(':count variations', ['count' => 4]) }}
                        </span>
                    </template>

                    <div class="absolute right-0 top-0 px-3 py-3 opacity-0 transition-all duration-300 group-hover:opacity-100">
                        <x-dropdown.dropdown
                            class:dropdown-dropdown="max-lg:end-0 max-lg:start-auto"
                            anchor="end"
                            :teleport="false"
                        >
                            <x-slot:trigger
                                class="size-8"
                            >
                                <x-tabler-dots-vertical class="size-7 rounded-full bg-white/90 p-1 text-black/70 shadow-sm hover:text-foreground" />
                                <span class="sr-only">{{ __('Options') }}</span>
                            </x-slot:trigger>
                            <x-slot:dropdown
                                class="min-w-[170px]"
                            >
                                <ul class="py-1 text-xs font-medium">
                                    <li>
                                        <a
                                            class="text-heading-foreground/2 flex px-5 py-2 transition-colors hover:bg-heading-foreground/[3%]"
                                            href="javascript:void(0);"
                                            @click.prevent="makeAction(image.id, 'download'); toggle('collapse')"
                                        >
                                            <x-tabler-download class="me-2 size-5" />
                                            {{ __('Download') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            class="text-heading-foreground/2 flex px-5 py-2 transition-colors hover:bg-heading-foreground/[3%]"
                                            href="javascript:void(0);"
                                            @click.prevent="makeAction(image.id, 'edit'); toggle('collapse')"
                                        >
                                            <x-tabler-scissors class="me-2 size-5" />
                                            {{ __('Edit') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            class="text-heading-foreground/2 flex px-5 py-2 transition-colors hover:bg-heading-foreground/[3%]"
                                            href="javascript:void(0);"
                                            @click.prevent="makeAction(image.id, 'video'); toggle('collapse')"
                                        >
                                            <x-tabler-video class="me-2 size-5" />
                                            {{ __('Create Video') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            class="text-heading-foreground/2 flex px-5 py-2 transition-colors hover:bg-heading-foreground/[3%]"
                                            href="javascript:void(0);"
                                            @click.prevent="makeAction(image.id, 'remove'); toggle('collapse')"
                                        >
                                            <x-tabler-circle-minus class="me-2 size-5" />
                                            {{ __('Remove') }}
                                        </a>
                                    </li>
                                </ul>
                            </x-slot:dropdown>
                        </x-dropdown.dropdown>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Image Preview Modal --}}
    <div
        class="lqd-modal-img group/modal invisible fixed start-0 top-0 z-[999] grid h-screen w-screen place-items-center border px-3 opacity-0 md:px-5 [&.is-active]:visible [&.is-active]:opacity-100"
        id="modal_image_latest"
        :class="{ 'is-active': modalShow }"
    >
        <div
            class="lqd-modal-img-backdrop absolute start-0 top-0 z-0 h-screen w-screen bg-black/50 opacity-0 transition-opacity group-[&.is-active]/modal:opacity-100"
            @click="isCropMode ? null : (modalShow = false)"
        ></div>

        <div class="lqd-modal-img-content-wrap relative z-10 w-full max-w-6xl">
            <div class="lqd-modal-img-content relative flex max-h-[90vh] w-full translate-y-2 scale-[0.985] flex-wrap justify-between gap-4 overflow-y-auto overscroll-contain rounded-xl bg-background p-3 opacity-0 md:min-h-[min(90vh,570px)] md:gap-0 md:p-5 shadow-2xl transition-all group-[&.is-active]/modal:translate-y-0 group-[&.is-active]/modal:scale-100 group-[&.is-active]/modal:opacity-100">
                <a
                    class="absolute end-2 top-3 z-10 flex size-9 items-center justify-center rounded-full border bg-background text-inherit shadow-sm transition-all hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black"
                    @click.prevent="isCropMode ? exitCropMode() : (modalShow = false)"
                    href="javascript:void(0);"
                >
                    <x-tabler-x class="size-4" />
                </a>

                {{-- Normal Preview Mode --}}
                <template x-if="!isCropMode">
                    <figure class="lqd-modal-fig relative flex min-h-[1px] w-full items-center justify-center rounded-lg bg-foreground/5 max-md:aspect-square md:w-6/12 md:min-h-[min(80vh,540px)]">
                        <img
                            class="lqd-modal-img mx-auto max-h-full max-w-full object-contain object-center transition-opacity duration-200"
                            :class="imageLoading ? 'opacity-0' : 'opacity-100'"
                            :src="activeItem?.output"
                            :alt="activeItem?.input"
                            @load="onActiveImageLoaded($event)"
                            x-on:error="imageLoading = false"
                        />
                        <div
                            x-show="imageLoading"
                            x-transition.opacity
                            class="absolute inset-0 grid place-items-center"
                        >
                            <x-tabler-loader-2 class="size-8 animate-spin text-primary" />
                        </div>
                    </figure>
                </template>

                {{-- Crop Mode --}}
                <template x-if="isCropMode">
                    <div class="relative min-h-[1px] w-full md:w-6/12">
                        <div
                            class="relative h-full w-full overflow-hidden rounded-lg bg-foreground/5"
                            style="min-height: 400px;"
                        >
                            <img
                                class="w-full max-w-full"
                                id="cropImageLatest"
                                :src="activeItem?.output"
                                alt="Crop preview"
                                style="display: block; max-height: 60vh;"
                            />
                        </div>
                    </div>
                </template>

                {{-- Sidebar - Normal Mode --}}
                <template x-if="!isCropMode">
                    <div class="relative flex w-full flex-col p-3 md:w-5/12">
                        <div class="relative flex flex-col items-start pb-6">
                            <h3 class="mb-6">{{ __('Photoshoot Details') }}</h3>

                            <template x-if="getProductUrls().length > 0">
                                <div class="mb-6 flex w-full items-center justify-between gap-1">
                                    <p class="m-0 text-xs font-medium">
                                        {{ __('Product:') }}
                                    </p>
                                    <div class="flex gap-3">
                                        <template
                                            x-for="(productUrl, idx) in getProductUrls()"
                                            :key="idx"
                                        >
                                            <img
                                                class="size-16 rounded border object-cover object-center"
                                                :src="productUrl"
                                                :alt="'Product ' + (idx + 1)"
                                            />
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <template x-if="activeItem?.parsedPayload?.prompt">
                                <div class="mb-6 w-full">
                                    <p class="mb-1.5 m-0 text-xs font-medium">
                                        {{ __('Prompt:') }}
                                    </p>
                                    <p
                                        class="m-0 text-2xs opacity-70"
                                        x-text="activeItem.parsedPayload.prompt"
                                    ></p>
                                </div>
                            </template>

                            <div class="mb-6 w-full space-y-4">
                                <template
                                    x-for="detail in getActiveItemDetails()"
                                    :key="detail.label"
                                >
                                    <div class="flex items-center justify-between py-1.5">
                                        <span
                                            class="text-2xs"
                                            x-text="detail.label"
                                        ></span>
                                        <span
                                            class="text-2xs font-medium opacity-50"
                                            x-text="detail.value"
                                        ></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mt-auto space-y-3">
                            <div class="grid grid-cols-2 gap-1.5">
                                <x-button
                                    class="text-xs font-medium"
                                    @click.prevent="makeAction(activeItem.id, 'edit')"
                                    variant="outline"
                                    size="lg"
                                >
                                    {{ __('Edit') }}
                                </x-button>
                                <x-button
                                    class="text-xs font-medium"
                                    @click.prevent="enterCropMode()"
                                    variant="outline"
                                    size="lg"
                                >
                                    {{ __('Crop') }}
                                </x-button>
                            </div>

                            <x-button
                                class="w-full text-xs font-medium"
                                @click.prevent="makeAction(activeItem.id, 'download')"
                                size="lg"
                            >
                                {{ __('Download') }}
                            </x-button>
                        </div>
                    </div>
                </template>

                {{-- Sidebar - Crop Mode --}}
                <template x-if="isCropMode">
                    <div class="relative flex w-full flex-col p-3 md:w-5/12">
                        <h3 class="mb-4">{{ __('Crop Image') }}</h3>

                        <div class="mb-6">
                            <p class="mb-3 text-xs font-medium">{{ __('Aspect Ratio') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <x-button
                                    class="text-xs font-medium [&.active]:bg-primary [&.active]:text-primary-foreground"
                                    type="button"
                                    variant="outline"
                                    ::class="{ 'active': !cropAspectRatio }"
                                    @click="setCropAspectRatio(null)"
                                >
                                    {{ __('Free') }}
                                </x-button>
                                <x-button
                                    class="text-xs font-medium [&.active]:bg-primary [&.active]:text-primary-foreground"
                                    type="button"
                                    variant="outline"
                                    ::class="{ 'active': cropAspectRatio === 1 }"
                                    @click="setCropAspectRatio(1)"
                                >
                                    1:1
                                </x-button>
                                <x-button
                                    class="text-xs font-medium [&.active]:bg-primary [&.active]:text-primary-foreground"
                                    type="button"
                                    variant="outline"
                                    ::class="{ 'active': cropAspectRatio === 4 / 3 }"
                                    @click="setCropAspectRatio(4/3)"
                                >
                                    4:3
                                </x-button>
                                <x-button
                                    class="text-xs font-medium [&.active]:bg-primary [&.active]:text-primary-foreground"
                                    type="button"
                                    variant="outline"
                                    ::class="{ 'active': cropAspectRatio === 16 / 9 }"
                                    @click="setCropAspectRatio(16/9)"
                                >
                                    16:9
                                </x-button>
                                <x-button
                                    class="text-xs font-medium [&.active]:bg-primary [&.active]:text-primary-foreground"
                                    type="button"
                                    variant="outline"
                                    ::class="{ 'active': cropAspectRatio === 3 / 4 }"
                                    @click="setCropAspectRatio(3/4)"
                                >
                                    3:4
                                </x-button>
                                <x-button
                                    class="text-xs font-medium [&.active]:bg-primary [&.active]:text-primary-foreground"
                                    type="button"
                                    variant="outline"
                                    ::class="{ 'active': cropAspectRatio === 9 / 16 }"
                                    @click="setCropAspectRatio(9/16)"
                                >
                                    9:16
                                </x-button>
                            </div>
                        </div>

                        <div class="mt-auto space-y-3">
                            <x-button
                                class="w-full text-xs font-medium"
                                type="button"
                                variant="primary"
                                size="lg"
                                @click="saveCroppedImage()"
                                ::disabled="cropSaving"
                            >
                                <span x-show="!cropSaving">{{ __('Save Cropped Image') }}</span>
                                <span
                                    class="flex items-center gap-2"
                                    x-show="cropSaving"
                                >
                                    <x-tabler-loader-2 class="size-4 animate-spin" />
                                    {{ __('Saving...') }}
                                </span>
                            </x-button>
                            <x-button
                                class="w-full text-xs font-medium"
                                type="button"
                                variant="outline"
                                size="lg"
                                @click="exitCropMode()"
                            >
                                {{ __('Cancel') }}
                            </x-button>
                        </div>
                    </div>
                </template>
            </div>

            <template x-if="!isCropMode">
                <a
                    class="absolute -start-1 top-1/2 z-10 inline-flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-background text-inherit shadow-md transition-all hover:scale-110 hover:bg-primary hover:text-primary-foreground lg:-start-4"
                    href="javascript:void(0);"
                    @click.prevent="prevItem()"
                >
                    <x-tabler-chevron-left class="size-5" />
                </a>
            </template>

            <template x-if="!isCropMode">
                <a
                    class="absolute -end-1 top-1/2 z-10 inline-flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-background text-inherit shadow-md transition-all hover:scale-110 hover:bg-primary hover:text-primary-foreground lg:-end-4"
                    href="javascript:void(0);"
                    @click.prevent="nextItem()"
                >
                    <x-tabler-chevron-right class="size-5" />
                </a>
            </template>
        </div>
    </div>
</div>

@push('script')
    <script src="{{ custom_theme_url('/assets/libs/cropperjs/cropper.min.js') }}"></script>
    <script>
        const APS_LATEST_ROUTES = {
            loadImages: '{{ route('dashboard.user.ai-photoshoot.photo_shoots.images.load') }}',
            removeImage: '{{ route('dashboard.user.ai-photoshoot.photo_shoots.images.remove') }}',
            cropImage: '{{ route('dashboard.user.ai-photoshoot.photo_shoots.images.crop') }}',
            editImage: '{{ route('dashboard.user.ai-photoshoot.editor.index') }}',
            createVideo: '{{ route('dashboard.user.ai-photoshoot.create_video.index') }}',
            csrfToken: '{{ csrf_token() }}',
        };

        const APS_LATEST_MESSAGES = {
            loadError: '{{ __('Failed to load images. Please try again.') }}',
            notFound: '{{ __('Image not found.') }}',
            cropSuccess: '{{ __('Image cropped successfully.') }}',
            cropFailed: '{{ __('Failed to crop image.') }}',
            removeConfirm: '{{ __('Are you sure you want to remove this image?') }}',
            removeSuccess: '{{ __('Image removed successfully.') }}',
            removeFailed: '{{ __('Failed to remove image.') }}',
            unknownAction: '{{ __('Unknown action.') }}'
        };

        function apsLatestParsePayload(image) {
            if (!image?.payload) return {};
            if (typeof image.payload === 'object') return image.payload;
            try {
                return JSON.parse(image.payload);
            } catch (e) {
                return {};
            }
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('latestPhotoshootsComponent', () => ({
                images: [],
                modalShow: false,
                activeItem: null,
                activeItemId: null,
                loading: true,
                imageLoading: false,

                isCropMode: false,
                cropAspectRatio: null,
                cropSaving: false,
                cropper: null,

                init() {
                    this.loadImages();
                },

                setActiveItem(data) {
                    const isSame = this.activeItem && this.activeItem.output === data.output;
                    this.activeItem = { ...data, naturalWidth: null, naturalHeight: null };
                    this.activeItemId = data.id;
                    if (!isSame) {
                        this.imageLoading = true;
                    }
                },

                onActiveImageLoaded(event) {
                    this.imageLoading = false;
                    const img = event?.target;
                    if (!img || !this.activeItem) return;
                    if (!img.naturalWidth || !img.naturalHeight) return;
                    this.activeItem = {
                        ...this.activeItem,
                        naturalWidth: img.naturalWidth,
                        naturalHeight: img.naturalHeight,
                    };
                },

                formatRatio(width, height) {
                    if (!width || !height) return null;
                    const ratio = width / height;
                    const presets = [
                        { label: '21:9', value: 21 / 9 },
                        { label: '16:9', value: 16 / 9 },
                        { label: '3:2', value: 3 / 2 },
                        { label: '4:3', value: 4 / 3 },
                        { label: '1:1', value: 1 },
                        { label: '4:5', value: 4 / 5 },
                        { label: '3:4', value: 3 / 4 },
                        { label: '9:16', value: 9 / 16 },
                    ];
                    for (const p of presets) {
                        if (Math.abs(ratio - p.value) / p.value < 0.03) return p.label;
                    }
                    const gcd = (a, b) => b ? gcd(b, a % b) : a;
                    const w = Math.round(width);
                    const h = Math.round(height);
                    const d = gcd(w, h) || 1;
                    return `${w / d}:${h / d}`;
                },

                getProductUrls() {
                    const payload = this.activeItem?.parsedPayload || {};
                    if (Array.isArray(payload.product_urls) && payload.product_urls.length) {
                        return payload.product_urls;
                    }
                    if (payload.product_url) {
                        return [payload.product_url];
                    }
                    return [];
                },

                getActiveItemDetails() {
                    if (!this.activeItem) return [];
                    const details = [];
                    const payload = this.activeItem.parsedPayload || {};

                    details.push({ label: '{{ __('Date') }}', value: this.activeItem.format_date || '{{ __('None') }}' });
                    if (payload.template_label) details.push({ label: '{{ __('Template') }}', value: payload.template_label });
                    if (payload.background_name) details.push({ label: '{{ __('Background') }}', value: payload.background_name });
                    if (payload.resolution) details.push({ label: '{{ __('Resolution') }}', value: payload.resolution });

                    const ratioLabels = @json(\App\Extensions\AIPhotoshoot\System\Models\AIPhotoshootUserSetting::RATIO_LABELS);
                    const labelForSelection = payload.ratio && ratioLabels[payload.ratio];

                    if (labelForSelection) {
                        details.push({
                            label: '{{ __('Ratio') }}',
                            value: labelForSelection,
                        });
                    } else {
                        let ratioText = this.formatRatio(this.activeItem.naturalWidth, this.activeItem.naturalHeight);

                        if (!ratioText && payload.cropped_width && payload.cropped_height) {
                            ratioText = this.formatRatio(payload.cropped_width, payload.cropped_height);
                        }
                        if (!ratioText && payload.aspect_ratio) {
                            ratioText = this.formatRatio(parseFloat(payload.aspect_ratio), 1);
                        }
                        if (!ratioText && payload.image_width && payload.image_height) {
                            ratioText = this.formatRatio(payload.image_width, payload.image_height);
                        }
                        if (!ratioText && payload.ratio && payload.ratio !== 'auto') {
                            ratioText = payload.ratio;
                        }

                        if (ratioText) {
                            const descriptions = @json(\App\Extensions\AIPhotoshoot\System\Models\AIPhotoshootUserSetting::RATIO_DESCRIPTIONS);
                            const description = descriptions[ratioText];
                            details.push({
                                label: '{{ __('Ratio') }}',
                                value: description ? `${ratioText} (${description})` : ratioText,
                            });
                        }
                    }

                    return details;
                },

                navigateItem(direction) {
                    const currentIndex = this.images.findIndex(img => img.id === this.activeItemId);
                    const newIndex = currentIndex + direction;

                    if (newIndex >= 0 && newIndex < this.images.length) {
                        const newImage = this.images[newIndex];
                        this.setActiveItem({
                            ...newImage,
                            output: newImage.url
                        });
                    }
                },

                prevItem() {
                    this.navigateItem(-1);
                },

                nextItem() {
                    this.navigateItem(1);
                },

                async loadImages() {
                    this.loading = true;

                    try {
                        const url = new URL(APS_LATEST_ROUTES.loadImages, window.location.origin);
                        url.searchParams.set('page', '1');

                        const response = await fetch(url.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) throw new Error('Failed to fetch images');

                        const data = await response.json();
                        this.images = (data.images || [])
                            .slice(0, 12)
                            .map(img => ({ ...img, parsedPayload: apsLatestParsePayload(img) }));
                    } catch (error) {
                        console.error('Failed to load images:', error);
                        window.toastr?.error(APS_LATEST_MESSAGES.loadError);
                    } finally {
                        this.loading = false;
                    }
                },

                async makeAction(imageId, action) {
                    const image = this.images.find(img => img.id === imageId);
                    if (!image) {
                        return window.toastr?.error(APS_LATEST_MESSAGES.notFound);
                    }

                    if (action === 'download') {
                        this.downloadImage(image);
                    } else if (action === 'edit') {
                        this.redirectToEdit(image);
                    } else if (action === 'video') {
                        this.redirectToVideo(image);
                    } else if (action === 'remove') {
                        this.removeImage(imageId);
                    } else {
                        window.toastr?.error(APS_LATEST_MESSAGES.unknownAction);
                    }
                },

                enterCropMode() {
                    this.isCropMode = true;
                    this.cropAspectRatio = null;

                    this.$nextTick(() => {
                        setTimeout(() => this.initCropper(), 100);
                    });
                },

                exitCropMode() {
                    this.isCropMode = false;
                    this.cropAspectRatio = null;
                    if (this.cropper) {
                        this.cropper.destroy();
                        this.cropper = null;
                    }
                },

                initCropper() {
                    const imageElement = document.getElementById('cropImageLatest');
                    if (!imageElement) return;

                    if (this.cropper) {
                        this.cropper.destroy();
                        this.cropper = null;
                    }

                    this.cropper = new Cropper(imageElement, {
                        viewMode: 1,
                        dragMode: 'move',
                        aspectRatio: this.cropAspectRatio,
                        autoCropArea: 0.8,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                        responsive: true,
                    });
                },

                setCropAspectRatio(ratio) {
                    this.cropAspectRatio = ratio;
                    if (this.cropper) {
                        this.cropper.setAspectRatio(ratio);
                    }
                },

                async saveCroppedImage() {
                    if (!this.cropper || !this.activeItemId) return;

                    this.cropSaving = true;

                    try {
                        const canvas = this.cropper.getCroppedCanvas({
                            maxWidth: 4096,
                            maxHeight: 4096,
                            fillColor: '#fff',
                            imageSmoothingEnabled: true,
                            imageSmoothingQuality: 'high',
                        });

                        if (!canvas) throw new Error('Failed to get cropped canvas');

                        const width = canvas.width;
                        const height = canvas.height;
                        const aspectRatio = width / height;
                        const imageData = canvas.toDataURL('image/png');

                        const response = await fetch(APS_LATEST_ROUTES.cropImage, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': APS_LATEST_ROUTES.csrfToken
                            },
                            body: JSON.stringify({
                                image_id: this.activeItemId,
                                image_data: imageData,
                                width: width,
                                height: height,
                                aspect_ratio: aspectRatio
                            })
                        });

                        const result = await response.json();
                        if (!response.ok) throw new Error(result.error || 'Failed to save cropped image');

                        const newPayload = {
                            ...(this.activeItem.parsedPayload || {}),
                            cropped_width: result.width,
                            cropped_height: result.height,
                            aspect_ratio: result.aspect_ratio
                        };
                        const newImage = {
                            ...this.activeItem,
                            id: result.image_id,
                            url: result.url,
                            thumbnail: result.thumbnail || result.url,
                            output: result.url,
                            payload: newPayload,
                            parsedPayload: newPayload
                        };
                        this.images.unshift(newImage);
                        this.setActiveItem(newImage);

                        window.toastr?.success(APS_LATEST_MESSAGES.cropSuccess);
                        this.exitCropMode();
                    } catch (error) {
                        console.error('Error saving cropped image:', error);
                        window.toastr?.error(APS_LATEST_MESSAGES.cropFailed);
                    } finally {
                        this.cropSaving = false;
                    }
                },

                redirectToEdit(image) {
                    sessionStorage.setItem('pendingImageForEditor', JSON.stringify({
                        url: image.url,
                        title: image.input || image.prompt || `image_${image.id}`,
                    }));
                    window.location.href = APS_LATEST_ROUTES.editImage;
                },

                redirectToVideo(image) {
                    sessionStorage.setItem('apsCreateVideoData', JSON.stringify({
                        url: image.url,
                        id: image.id,
                        fileName: `image_${image.id}.jpg`
                    }));
                    window.location.href = APS_LATEST_ROUTES.createVideo + '/' + image.id;
                },

                downloadImage(image) {
                    const link = document.createElement('a');
                    link.href = image.url;
                    link.download = `image_${image.id}_${Date.now()}.jpg`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                },

                async removeImage(imageId) {
                    if (!confirm(APS_LATEST_MESSAGES.removeConfirm)) return;

                    try {
                        const response = await fetch(APS_LATEST_ROUTES.removeImage, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': APS_LATEST_ROUTES.csrfToken
                            },
                            body: JSON.stringify({ image_id: imageId })
                        });

                        if (!response.ok) throw new Error('Failed to remove image');

                        this.images = this.images.filter(img => img.id !== imageId);

                        if (this.activeItemId === imageId) {
                            this.modalShow = false;
                        }

                        window.toastr?.success(APS_LATEST_MESSAGES.removeSuccess);
                    } catch (error) {
                        console.error('Error removing image:', error);
                        window.toastr?.error(APS_LATEST_MESSAGES.removeFailed);
                    }
                },
            }));
        });
    </script>
@endpush
