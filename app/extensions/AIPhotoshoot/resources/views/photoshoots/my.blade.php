@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Photoshoots'))
@section('titlebar_pretitle', '')
@section('titlebar_actions')
    <x-button
        href="{{ route('dashboard.user.ai-photoshoot.custom.index') }}"
        variant="primary"
    >
        <x-tabler-plus class="size-4" />
        {{ __('New Photoshoot') }}
    </x-button>
@endsection
@section('titlebar_subtitle', __('View and manage your photoshoots.'))

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

@section('content')
    <div
        class="py-12"
        x-data="myPhotoShootsComponent"
        @keyup.escape.window="isCropMode ? exitCropMode() : (modalShow = false)"
        @keyup.arrow-left.window="modalShow && !isCropMode && prevItem()"
        @keyup.arrow-right.window="modalShow && !isCropMode && nextItem()"
    >
        {{-- Header: Tabs + Controls --}}
        <div class="mb-8 flex flex-col items-center justify-between gap-4 lg:flex-row">
            <div class="flex w-full grow flex-wrap items-center gap-2 lg:w-auto">
                <button
                    class="rounded-full px-6 py-3.5 text-base font-medium leading-none text-heading-foreground transition [&.selected]:bg-heading-foreground/5"
                    type="button"
                    :class="{ 'selected': currentFilter === 'all' }"
                    @click="changeFilter('all')"
                >
                    {{ __('All') }}
                </button>
                <button
                    class="rounded-full px-6 py-3.5 text-base font-medium leading-none text-heading-foreground transition [&.selected]:bg-heading-foreground/5"
                    type="button"
                    :class="{ 'selected': currentFilter === 'images' }"
                    @click="changeFilter('images')"
                >
                    {{ __('Images') }}
                </button>
                <button
                    class="rounded-full px-6 py-3.5 text-base font-medium leading-none text-heading-foreground transition [&.selected]:bg-heading-foreground/5"
                    type="button"
                    :class="{ 'selected': currentFilter === 'videos' }"
                    @click="changeFilter('videos')"
                >
                    {{ __('Videos') }}
                </button>
            </div>

            <div class="flex w-full grow flex-wrap items-center gap-3 lg:w-auto lg:justify-end">
                <div class="flex items-center gap-3 max-md:order-last">
                    <span class="whitespace-nowrap text-sm font-medium text-heading-foreground sm:hidden">
                        {{ __('Grid size') }}
                    </span>
                    <input
                        class="h-0.5 w-36 cursor-pointer appearance-none rounded-full bg-foreground/10 focus:outline-primary [&::-moz-range-thumb]:size-2.5 [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-none [&::-moz-range-thumb]:bg-primary active:[&::-moz-range-thumb]:scale-110 [&::-webkit-slider-thumb]:size-2.5 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-none [&::-webkit-slider-thumb]:bg-primary active:[&::-webkit-slider-thumb]:scale-110"
                        type="range"
                        x-model.number="gridSize"
                        @change="saveGridSize()"
                        min="2"
                        max="6"
                        step="1"
                    />
                </div>

                <form
                    class="relative flex max-md:w-full md:w-[min(100%,180px)] 2xl:w-[min(100%,330px)]"
                    @submit.prevent
                >
                    <x-tabler-search class="absolute start-5 top-1/2 size-5 -translate-y-1/2" />
                    <x-forms.input
                        class="h-auto w-full rounded-full border-none bg-foreground/5 py-4 ps-[52px] font-medium placeholder:text-foreground sm:text-[12px]"
                        container-class="grow"
                        placeholder="{{ __('Search') }}"
                        x-model.debounce.300ms="searchQuery"
                    />
                </form>

                <button
                    class="h-[50px] rounded-full bg-heading-foreground/5 px-6 py-2.5 text-[12px] font-medium leading-none text-heading-foreground hover:bg-primary hover:text-primary-foreground"
                    type="button"
                    @click.prevent="toggleSelectAll"
                    x-text="allDisplayedSelected ? '{{ __('Deselect all') }}' : '{{ __('Select all') }}'"
                >
                    {{ __('Select all') }}
                </button>

                <x-dropdown.dropdown offsetY="10px">
                    <x-slot:trigger
                        class="h-[50px] rounded-full bg-heading-foreground/5 px-6 py-2.5 text-[12px] font-medium leading-none text-heading-foreground"
                    >
                        <span x-text="sortLabels[sort]">
                            {{ __('Sort by Date') }}
                        </span>
                        <x-tabler-chevron-down class="size-4" />
                    </x-slot:trigger>

                    <x-slot:dropdown
                        class="p-1"
                    >
                        <button
                            class="flex w-full items-center gap-2 rounded px-3 py-2 text-start text-xs font-medium hover:bg-foreground/5 [&.active]:bg-foreground/5"
                            type="button"
                            :class="{ 'active': sort === 'date' }"
                            @click="setSort('date')"
                        >
                            {{ __('Date') }}
                            <x-tabler-caret-down
                                class="size-4 fill-current transition-transform [&.flipped]:rotate-180"
                                ::class="{ 'flipped': sortDirection === 'asc' }"
                                x-show="sort === 'date'"
                            />
                        </button>
                        <button
                            class="flex w-full items-center gap-2 rounded px-3 py-2 text-start text-xs font-medium hover:bg-foreground/5 [&.active]:bg-foreground/5"
                            type="button"
                            :class="{ 'active': sort === 'name' }"
                            @click="setSort('name')"
                        >
                            {{ __('Name') }}
                            <x-tabler-caret-down
                                class="size-4 fill-current transition-transform [&.flipped]:rotate-180"
                                ::class="{ 'flipped': sortDirection === 'asc' }"
                                x-show="sort === 'name'"
                            />
                        </button>
                    </x-slot:dropdown>
                </x-dropdown.dropdown>
            </div>
        </div>

        <p
            class="flex items-center gap-1"
            x-show="loading"
        >
            <x-tabler-loader-2 class="size-4 animate-spin" />
            <span x-text="currentFilter === 'videos' ? '{{ __('Loading videos') }}' : '{{ __('Loading images') }}'"></span>
        </p>

        <x-empty-state
            class="py-20"
            icon="tabler-photo-off"
            :title="__('No items found')"
            :description="__('Try a different search term.')"
            show="!loading && !displaySections.length && searchQuery"
            x-cloak
        />

        <x-empty-state
            class="py-20"
            icon="tabler-photo-off"
            :title="__('No items found')"
            :description="__('Start generating to see your photoshoots here.')"
            show="!loading && !displaySections.length && !searchQuery"
            x-cloak
        />

        <template x-if="!loading && displaySections.length">
            <template
                x-for="section in displaySections"
                :key="section.label"
            >
                <div class="mb-10">
                    <h3
                        class="text-muted-foreground mb-4 text-sm font-medium"
                        x-text="section.label"
                    ></h3>
                    <div
                        class="grid gap-1"
                        :style="`grid-template-columns: repeat(${gridSize}, minmax(0, 1fr))`"
                    >
                        <template
                            x-for="image in section.images"
                            :key="image.id"
                        >
                            <div
                                class="image-result group relative aspect-square break-inside-avoid"
                                :data-id="image.id"
                                :class="{ 'grid place-items-center text-center border': image.is_processing, 'cursor-pointer': !image.is_processing }"
                            >
                                <div
                                    class="relative size-full overflow-hidden"
                                    :class="{ 'grid place-items-center': image.is_processing }"
                                >
                                    {{-- Processing overlay for videos --}}
                                    <template x-if="image.is_processing">
                                        <div>
                                            <span class="mx-auto inline-grid size-7 animate-spin place-items-center">
                                                {{-- blade-formatter-disable --}}
                                                <svg width="27" height="27" viewBox="0 0 27 27" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.3333 26.6667C5.98667 26.6667 0 20.68 0 13.3333C0 10.84 0.693333 8.41333 2 6.30667C2.38667 5.68 3.21333 5.49333 3.84 5.88C4.46667 6.26667 4.65333 7.09332 4.26667 7.71999C3.22667 9.39999 2.66667 11.3467 2.66667 13.3333C2.66667 19.2133 7.45333 24 13.3333 24C19.2133 24 24 19.2133 24 13.3333C24 7.45333 19.2133 2.66667 13.3333 2.66667C12.6 2.66667 12 2.06667 12 1.33333C12 0.6 12.6 0 13.3333 0C20.68 0 26.6667 5.98667 26.6667 13.3333C26.6667 20.68 20.68 26.6667 13.3333 26.6667Z" fill="url(#paint0_linear_aps_my)"/><defs><linearGradient id="paint0_linear_aps_my" x1="0" y1="5.44" x2="22.3733" y2="25.1733" gradientUnits="userSpaceOnUse"><stop stop-color="hsl(var(--gradient-from))"/><stop offset="0.502" stop-color="hsl(var(--gradient-via))"/><stop offset="1" stop-color="hsl(var(--gradient-to))"/></linearGradient></defs></svg>
                                                {{-- blade-formatter-enable --}}
                                            </span>
                                            <p class="m-0 bg-gradient-to-br from-gradient-from via-gradient-via to-gradient-to bg-clip-text text-sm font-semibold text-transparent">
                                                {{ __('In Progress') }}
                                            </p>
                                        </div>
                                    </template>

                                    <template x-if="image.is_video && !image.is_processing">
                                        <video
                                            class="size-full object-cover object-center"
                                            :src="image.url"
                                            :alt="image.title || image.input"
                                            muted
                                            loop
                                            playsinline
                                            @mouseenter="$el.play()"
                                            @mouseleave="$el.pause(); $el.currentTime = 0;"
                                            @click="setActiveItem({...image, output: image.url}); modalShow = true"
                                        ></video>
                                    </template>
                                    <template x-if="!image.is_video && !image.is_processing">
                                        <img
                                            class="aspect-square h-auto w-full object-cover object-center"
                                            :src="`${(image.thumbnail || image.url).startsWith('upload') ? '/' : ''}${image.thumbnail || image.url}`"
                                            :alt="image.title || image.input"
                                            loading="lazy"
                                            @click="setActiveItem({...image, output: image.url}); modalShow = true"
                                        >
                                    </template>

                                    {{-- Selection Checkbox --}}
                                    <div
                                        class="absolute start-3 top-3 z-10"
                                        x-show="!image.is_processing"
                                        @click.stop
                                    >
                                        <label
                                            class="flex size-5 cursor-pointer items-center justify-center rounded border bg-white/80 opacity-0 transition group-hover:opacity-100 max-md:opacity-100 [&.selected]:border-primary [&.selected]:bg-primary [&.selected]:text-primary-foreground [&.selected]:opacity-100"
                                            :class="{ 'selected': isSelected(image.id) }"
                                        >
                                            <input
                                                class="sr-only"
                                                type="checkbox"
                                                :checked="isSelected(image.id)"
                                                @change="toggleSelect(image.id)"
                                            >
                                            <x-tabler-check
                                                class="size-4"
                                                x-show="isSelected(image.id)"
                                            />
                                        </label>
                                    </div>

                                    {{-- Video indicator --}}
                                    <template x-if="image.is_video && !image.is_processing">
                                        <div class="absolute bottom-2 start-2 flex items-center gap-1 rounded-full bg-black/70 px-2 py-1 text-white">
                                            <x-tabler-video class="size-4" />
                                            <span class="text-2xs font-medium">{{ __('Video') }}</span>
                                        </div>
                                    </template>

                                    {{-- TODO: make these dynamically generated --}}
                                    <template x-if="image.variations">
                                        <span class="absolute start-3 top-3 inline-block rounded-full bg-background px-3.5 py-2 text-xs font-medium text-heading-foreground">
                                            {{ __(':count variations', ['count' => 4]) }}
                                        </span>
                                    </template>

                                    <div
                                        class="absolute right-0 top-0 px-3 py-3 opacity-0 transition-all duration-300 group-hover:opacity-100"
                                        x-show="!image.is_processing"
                                    >
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
                                                    <template x-if="!image.is_video">
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
                                                    </template>
                                                    <template x-if="!image.is_video">
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
                                                    </template>
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
                </div>
            </template>
        </template>

        {{-- Bulk Actions Bar --}}
        <div
            class="pointer-events-none fixed bottom-20 end-0 start-0 z-[100] transition-all lg:bottom-8"
            x-cloak
            x-show="selectedItems.length"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
        >
            <div class="container">
                <form
                    class="pointer-events-auto flex flex-col justify-between gap-3 rounded-lg border border-foreground/5 bg-background px-6 py-4 shadow-xl shadow-black/5 md:flex-row md:items-center md:rounded-full md:py-2 md:pe-2"
                    @submit.prevent="applyBulkAction"
                >
                    <p class="m-0 text-sm font-medium">
                        <span x-text="selectedItems.length"></span>
                        <span x-show="selectedItems.length === 1">{{ __('item') }}</span>
                        <span x-show="selectedItems.length !== 1">{{ __('items') }}</span>
                        {{ __('selected') }}
                    </p>
                    <div class="flex items-center gap-3">
                        <x-forms.input
                            class="w-full rounded-full md:w-auto md:pe-12"
                            type="select"
                            size="md"
                            x-model="bulkAction"
                        >
                            <option value="delete">
                                {{ __('Move to Trash') }}
                            </option>
                        </x-forms.input>

                        <x-button
                            class="rounded-full"
                            type="submit"
                            size="md"
                            ::disabled="bulkLoading"
                        >
                            <span x-show="!bulkLoading">{{ __('Apply') }}</span>
                            <x-tabler-loader-2
                                class="size-4 animate-spin"
                                x-show="bulkLoading"
                            />
                        </x-button>

                        <button
                            class="ms-auto flex size-10 items-center justify-center rounded-full text-foreground/70 transition hover:bg-foreground/5 hover:text-foreground"
                            type="button"
                            @click="clearSelection"
                        >
                            <x-tabler-x class="size-5" />
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Image Preview Modal --}}
        <div
            class="lqd-modal-img group/modal invisible fixed start-0 top-0 z-[999] grid h-screen w-screen place-items-center border px-3 opacity-0 md:px-5 [&.is-active]:visible [&.is-active]:opacity-100"
            id="modal_image"
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
                            <template x-if="activeItem?.is_video">
                                <video
                                    class="lqd-modal-img mx-auto max-h-full max-w-full object-contain object-center transition-opacity duration-200"
                                    :class="imageLoading ? 'opacity-0' : 'opacity-100'"
                                    :src="activeItem?.output"
                                    :alt="activeItem?.input"
                                    controls
                                    autoplay
                                    loop
                                    @loadeddata="imageLoading = false"
                                    x-on:error="imageLoading = false"
                                ></video>
                            </template>
                            <template x-if="!activeItem?.is_video">
                                <img
                                    class="lqd-modal-img mx-auto max-h-full max-w-full object-contain object-center transition-opacity duration-200"
                                    :class="imageLoading ? 'opacity-0' : 'opacity-100'"
                                    :src="activeItem?.output"
                                    :alt="activeItem?.input"
                                    @load="onActiveImageLoaded($event)"
                                    x-on:error="imageLoading = false"
                                />
                            </template>
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
                                    id="cropImageMy"
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
                                <template x-if="!activeItem?.is_video">
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
                                </template>

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
@endsection

@push('script')
    <script src="{{ custom_theme_url('/assets/libs/cropperjs/cropper.min.js') }}"></script>
    <script>
        const APS_MY_ROUTES = {
            loadImages: '{{ route('dashboard.user.ai-photoshoot.photo_shoots.images.load') }}',
            removeImage: '{{ route('dashboard.user.ai-photoshoot.photo_shoots.images.remove') }}',
            cropImage: '{{ route('dashboard.user.ai-photoshoot.photo_shoots.images.crop') }}',
            editImage: '{{ route('dashboard.user.ai-photoshoot.editor.index') }}',
            createVideo: '{{ route('dashboard.user.ai-photoshoot.create_video.index') }}',
            csrfToken: '{{ csrf_token() }}'
        };

        const APS_MY_MESSAGES = {
            loadError: '{{ __('Failed to load images. Please try again.') }}',
            notFound: '{{ __('Image not found.') }}',
            cropSuccess: '{{ __('Image cropped successfully.') }}',
            cropFailed: '{{ __('Failed to crop image.') }}',
            removeConfirm: '{{ __('Are you sure you want to remove this image?') }}',
            bulkRemoveConfirm: '{{ __('Are you sure you want to remove the selected items?') }}',
            removeSuccess: '{{ __('Image removed successfully.') }}',
            removeFailed: '{{ __('Failed to remove image.') }}',
            bulkRemoveSuccess: '{{ __('Selected items removed.') }}',
            bulkRemoveFailed: '{{ __('Some items could not be removed.') }}',
            unknownAction: '{{ __('Unknown action.') }}'
        };

        const APS_MY_LABELS = {
            today: '{{ __('Today') }}',
            yesterday: '{{ __('Yesterday') }}',
            older: '{{ __('Older') }}',
            results: '{{ __('Results') }}',
            sortDate: '{{ __('Sort by Date') }}',
            sortName: '{{ __('Sort by Name') }}',
        };

        const APS_MY_GRID_KEY = 'apsMyGridSize';

        function apsParsePayload(image) {
            if (!image?.payload) return {};
            if (typeof image.payload === 'object') return image.payload;
            try {
                return JSON.parse(image.payload);
            } catch (e) {
                return {};
            }
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('myPhotoShootsComponent', () => ({
                todayImages: [],
                yesterdayImages: [],
                olderImages: [],
                modalShow: false,
                activeItem: null,
                activeItemId: null,
                loading: true,
                imageLoading: false,
                refreshInterval: null,
                currentFilter: 'all',

                gridSize: 4,
                searchQuery: '',
                sort: 'date',
                sortDirection: 'desc',
                sortLabels: {
                    date: APS_MY_LABELS.sortDate,
                    name: APS_MY_LABELS.sortName,
                },
                selectedItems: [],
                bulkAction: 'delete',
                bulkLoading: false,

                isCropMode: false,
                cropAspectRatio: null,
                cropSaving: false,
                cropper: null,

                init() {
                    const savedGrid = parseInt(localStorage.getItem(APS_MY_GRID_KEY) || '', 10);
                    if (!isNaN(savedGrid) && savedGrid >= 2 && savedGrid <= 6) {
                        this.gridSize = savedGrid;
                    }

                    this.loadImages();
                    this.refreshInterval = setInterval(() => {
                        const hasProcessing = this.getAllImages().some(img => img.is_processing);
                        if (hasProcessing) {
                            this.loadImages(true);
                        }
                    }, 5000);
                },

                changeFilter(filter) {
                    if (this.currentFilter === filter) return;
                    this.currentFilter = filter;
                    this.clearSelection();
                    this.loadImages();
                },

                saveGridSize() {
                    localStorage.setItem(APS_MY_GRID_KEY, String(this.gridSize));
                },

                setSort(field) {
                    if (this.sort === field) {
                        this.sortDirection = this.sortDirection === 'desc' ? 'asc' : 'desc';
                    } else {
                        this.sort = field;
                        this.sortDirection = 'desc';
                    }
                },

                matchesSearch(image) {
                    const q = (this.searchQuery || '').trim().toLowerCase();
                    if (!q) return true;
                    const haystack = [
                        image.title,
                        image.input,
                        image.parsedPayload?.prompt,
                        image.parsedPayload?.template_label,
                        image.parsedPayload?.background_name,
                    ]
                        .filter(Boolean)
                        .join(' ')
                        .toLowerCase();
                    return haystack.includes(q);
                },

                sortByName(images) {
                    const sorted = [...images];
                    sorted.sort((a, b) => {
                        const aKey = (a.title || a.input || '').toLowerCase();
                        const bKey = (b.title || b.input || '').toLowerCase();
                        return aKey.localeCompare(bKey);
                    });
                    return this.sortDirection === 'asc' ? sorted : sorted.reverse();
                },

                get displaySections() {
                    const today = this.todayImages.filter(img => this.matchesSearch(img));
                    const yesterday = this.yesterdayImages.filter(img => this.matchesSearch(img));
                    const older = this.olderImages.filter(img => this.matchesSearch(img));

                    if (this.sort === 'name') {
                        const all = this.sortByName([...today, ...yesterday, ...older]);
                        return all.length ? [{ label: APS_MY_LABELS.results, images: all }] : [];
                    }

                    if (this.sortDirection === 'asc') {
                        const sections = [
                            { label: APS_MY_LABELS.older, images: [...older].reverse() },
                            { label: APS_MY_LABELS.yesterday, images: [...yesterday].reverse() },
                            { label: APS_MY_LABELS.today, images: [...today].reverse() },
                        ];
                        return sections.filter(s => s.images.length);
                    }

                    const sections = [
                        { label: APS_MY_LABELS.today, images: today },
                        { label: APS_MY_LABELS.yesterday, images: yesterday },
                        { label: APS_MY_LABELS.older, images: older },
                    ];
                    return sections.filter(s => s.images.length);
                },

                get displayedImages() {
                    return this.displaySections.flatMap(s => s.images);
                },

                get selectableImageIds() {
                    return this.displayedImages.filter(i => !i.is_processing).map(i => i.id);
                },

                get allDisplayedSelected() {
                    const ids = this.selectableImageIds;
                    return ids.length > 0 && ids.every(id => this.selectedItems.includes(id));
                },

                isSelected(id) {
                    return this.selectedItems.includes(id);
                },

                toggleSelect(id) {
                    const idx = this.selectedItems.indexOf(id);
                    if (idx === -1) {
                        this.selectedItems.push(id);
                    } else {
                        this.selectedItems.splice(idx, 1);
                    }
                },

                toggleSelectAll() {
                    const ids = this.selectableImageIds;
                    if (this.allDisplayedSelected) {
                        this.selectedItems = this.selectedItems.filter(id => !ids.includes(id));
                    } else {
                        const set = new Set([...this.selectedItems, ...ids]);
                        this.selectedItems = Array.from(set);
                    }
                },

                clearSelection() {
                    this.selectedItems = [];
                },

                async applyBulkAction() {
                    if (!this.selectedItems.length || this.bulkLoading) return;

                    if (this.bulkAction === 'delete') {
                        if (!confirm(APS_MY_MESSAGES.bulkRemoveConfirm)) return;

                        this.bulkLoading = true;
                        const ids = [...this.selectedItems];
                        let failed = 0;

                        try {
                            await Promise.all(ids.map(async (id) => {
                                try {
                                    const response = await fetch(APS_MY_ROUTES.removeImage, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': APS_MY_ROUTES.csrfToken,
                                        },
                                        body: JSON.stringify({ image_id: id }),
                                    });
                                    if (!response.ok) throw new Error();
                                    this.todayImages = this.todayImages.filter(img => img.id !== id);
                                    this.yesterdayImages = this.yesterdayImages.filter(img => img.id !== id);
                                    this.olderImages = this.olderImages.filter(img => img.id !== id);
                                    if (this.activeItemId === id) {
                                        this.modalShow = false;
                                    }
                                } catch (e) {
                                    failed++;
                                }
                            }));

                            if (failed) {
                                window.toastr?.error(APS_MY_MESSAGES.bulkRemoveFailed);
                            } else {
                                window.toastr?.success(APS_MY_MESSAGES.bulkRemoveSuccess);
                            }
                        } finally {
                            this.clearSelection();
                            this.bulkLoading = false;
                        }
                    }
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

                getAllImages() {
                    return [...this.todayImages, ...this.yesterdayImages, ...this.olderImages];
                },

                navigateItem(direction) {
                    const allImages = this.getAllImages();
                    const currentIndex = allImages.findIndex(img => img.id === this.activeItemId);
                    const newIndex = currentIndex + direction;
                    if (newIndex >= 0 && newIndex < allImages.length) {
                        const newImage = allImages[newIndex];
                        this.setActiveItem({ ...newImage, output: newImage.url });
                    }
                },

                prevItem() { this.navigateItem(-1); },
                nextItem() { this.navigateItem(1); },

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

                async loadImages(silent = false) {
                    if (!silent) {
                        this.loading = true;
                    }
                    try {
                        const url = new URL(APS_MY_ROUTES.loadImages, window.location.origin);
                        url.searchParams.set('page', '1');
                        url.searchParams.set('type', this.currentFilter);

                        const response = await fetch(url.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) throw new Error('Failed to fetch images');

                        const data = await response.json();
                        const images = (data.images || []).map(img => ({ ...img, parsedPayload: apsParsePayload(img) }));

                        this.todayImages = images.filter(img => img.is_today);
                        this.yesterdayImages = images.filter(img => img.is_yesterday);
                        this.olderImages = images.filter(img => img.is_older);
                    } catch (error) {
                        console.error('Failed to load images:', error);
                        if (!silent) {
                            window.toastr?.error(APS_MY_MESSAGES.loadError);
                        }
                    } finally {
                        if (!silent) {
                            this.loading = false;
                        }
                    }
                },

                async makeAction(imageId, action) {
                    const image = this.getAllImages().find(img => img.id === imageId);
                    if (!image) {
                        return window.toastr?.error(APS_MY_MESSAGES.notFound);
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
                        window.toastr?.error(APS_MY_MESSAGES.unknownAction);
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
                    const imageElement = document.getElementById('cropImageMy');
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

                        const response = await fetch(APS_MY_ROUTES.cropImage, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': APS_MY_ROUTES.csrfToken
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
                            is_today: true,
                            is_yesterday: false,
                            is_older: false,
                            payload: newPayload,
                            parsedPayload: newPayload
                        };
                        this.todayImages.unshift(newImage);
                        this.setActiveItem(newImage);

                        window.toastr?.success(APS_MY_MESSAGES.cropSuccess);
                        this.exitCropMode();
                    } catch (error) {
                        console.error('Error saving cropped image:', error);
                        window.toastr?.error(APS_MY_MESSAGES.cropFailed);
                    } finally {
                        this.cropSaving = false;
                    }
                },

                redirectToEdit(image) {
                    sessionStorage.setItem('pendingImageForEditor', JSON.stringify({
                        url: image.url,
                        title: image.input || image.prompt || `image_${image.id}`,
                    }));
                    window.location.href = APS_MY_ROUTES.editImage;
                },

                redirectToVideo(image) {
                    sessionStorage.setItem('apsCreateVideoData', JSON.stringify({
                        url: image.url,
                        id: image.id,
                        fileName: `image_${image.id}.jpg`
                    }));
                    window.location.href = APS_MY_ROUTES.createVideo + '/' + image.id;
                },

                downloadImage(image) {
                    const link = document.createElement('a');
                    link.href = image.url;
                    const extension = image.is_video ? 'mp4' : 'jpg';
                    link.download = `${image.is_video ? 'video' : 'image'}_${image.id}_${Date.now()}.${extension}`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                },

                async removeImage(imageId) {
                    if (!confirm(APS_MY_MESSAGES.removeConfirm)) return;

                    try {
                        const response = await fetch(APS_MY_ROUTES.removeImage, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': APS_MY_ROUTES.csrfToken
                            },
                            body: JSON.stringify({ image_id: imageId })
                        });

                        if (!response.ok) throw new Error('Failed to remove image');

                        this.todayImages = this.todayImages.filter(img => img.id !== imageId);
                        this.yesterdayImages = this.yesterdayImages.filter(img => img.id !== imageId);
                        this.olderImages = this.olderImages.filter(img => img.id !== imageId);

                        if (this.activeItemId === imageId) {
                            this.modalShow = false;
                        }

                        window.toastr?.success(APS_MY_MESSAGES.removeSuccess);
                    } catch (error) {
                        console.error('Error removing image:', error);
                        window.toastr?.error(APS_MY_MESSAGES.removeFailed);
                    }
                },
            }));
        });
    </script>
@endpush
