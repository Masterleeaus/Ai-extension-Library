@php
    $apsMaxUploadMb = \App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry::getMaxUploadSizeMb();
@endphp
@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Template Photoshoot'))
@section('titlebar_pretitle', '')
@section('titlebar_actions')
    <x-button
        href="{{ route('dashboard.user.ai-photoshoot.photo_shoots.my') }}"
        variant="ghost-shadow"
    >
        {{ __('My Photoshoots') }}
    </x-button>

    <x-button
        href="{{ route('dashboard.user.ai-photoshoot.custom.index') }}"
        variant="primary"
    >
        <x-tabler-plus class="size-4" />
        {{ __('New Photoshoot') }}
    </x-button>
@endsection
@section('titlebar_subtitle', __('Upload product images and produce studio-quality photoshoots in seconds.'))

@section('content')
    <div
        class="py-10"
        x-data="templatePhotoApp"
    >
        <div class="flex flex-col gap-4 lg:flex-row lg:gap-9">
            {{-- Left: Set Up --}}
            <div class="w-full lg:basis-1/3">
                <p class="mb-5 border-b py-2.5 text-[12px] font-semibold transition-border">
                    {{ __('Set Up Photoshoot') }}
                </p>

                <form
                    class="space-y-4 sm:space-y-6"
                    @submit.prevent="submit"
                >
                    @csrf

                    {{-- Product Image (required) --}}
                    <div>
                        <label class="mb-4 flex items-center gap-1 text-2xs font-medium">
                            {{ __('Product Image') }}
                            <x-info-tooltip
                                class:content="-start-4 translate-x-0 text-2xs"
                                text="{{ __('Upload the product image to be placed into the selected template.') }}"
                            />
                        </label>

                        <input
                            class="hidden"
                            type="file"
                            x-ref="productInput"
                            accept="image/*"
                            @change="handleFileSelect($event)"
                        >

                        <div
                            class="cursor-pointer rounded-[10px] border border-dashed border-foreground/10 p-6 text-center transition hover:border-primary hover:bg-primary/5 sm:p-8"
                                @click="$refs.productInput.click()"
                                @dragover.prevent="dragOver = true"
                                @dragleave.prevent="dragOver = false"
                                @drop.prevent="handleFileDrop($event)"
                                :class="{ 'border-primary bg-primary/5': dragOver }"
                            >
                                <div x-show="!productPreview">
                                    <svg
                                        class="mx-auto mb-2.5 opacity-25"
                                        width="38"
                                        height="38"
                                        viewBox="0 0 38 38"
                                        fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M32.4073 32.4839C28.7298 36.1613 24.2608 38 19 38C13.7392 38 9.24462 36.1613 5.51613 32.4839C1.83871 28.7554 0 24.2608 0 19C0 13.7392 1.83871 9.27016 5.51613 5.59274C9.24462 1.86425 13.7392 0 19 0C24.2608 0 28.7298 1.86425 32.4073 5.59274C36.1358 9.27016 38 13.7392 38 19C38 24.2608 36.1358 28.7554 32.4073 32.4839ZM29.8024 8.19758C26.8401 5.18414 23.2392 3.67742 19 3.67742C14.7608 3.67742 11.1344 5.18414 8.12097 8.19758C5.1586 11.1599 3.67742 14.7608 3.67742 19C3.67742 23.2392 5.1586 26.8656 8.12097 29.879C11.1344 32.8414 14.7608 34.3226 19 34.3226C23.2392 34.3226 26.8401 32.8414 29.8024 29.879C32.8159 26.8656 34.3226 23.2392 34.3226 19C34.3226 14.7608 32.8159 11.1599 29.8024 8.19758ZM20.5323 28.8065H17.4677C16.8548 28.8065 16.5484 28.5 16.5484 27.8871V22C16.5484 20.3431 15.2052 19 13.5484 19H11.4153C11.0067 19 10.7258 18.8212 10.5726 18.4637C10.4194 18.0551 10.4704 17.7231 10.7258 17.4677L18.3871 9.80645C18.7957 9.39785 19.2043 9.39785 19.6129 9.80645L27.2742 17.4677C27.5296 17.7231 27.5806 18.0551 27.4274 18.4637C27.2742 18.8212 26.9933 19 26.5847 19H24.4516C22.7948 19 21.4516 20.3431 21.4516 22V27.8871C21.4516 28.5 21.1452 28.8065 20.5323 28.8065Z"
                                        />
                                    </svg>
                                    <p class="mb-2 text-sm font-medium">
                                        {{ __('Drag and drop or click to browse') }}
                                    </p>
                                    <p class="m-0 text-4xs font-medium opacity-50">
                                        {{ __('Max File Size: :size MB', ['size' => $apsMaxUploadMb]) }}
                                    </p>
                                </div>

                                <div
                                    x-show="productPreview"
                                    x-cloak
                                >
                                    <div class="relative mx-auto mb-2 inline-block w-32">
                                        <img
                                            class="max-h-40 w-full rounded-lg border object-cover"
                                            :src="productPreview"
                                        >
                                        <button
                                            class="absolute -end-3 -top-3 inline-grid size-7 place-items-center rounded-full bg-background text-foreground shadow-lg transition hover:scale-110 hover:bg-red-500 hover:text-white"
                                            type="button"
                                            @click.stop="removeProduct()"
                                            title="{{ __('Remove') }}"
                                        >
                                            <x-tabler-x class="size-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Ratio --}}
                        <x-forms.input
                            class:label="text-heading-foreground text-2xs font-medium"
                            label="{{ __('Ratio') }}"
                            type="select"
                            x-model="ratio"
                        >
                            @foreach (\App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry::getRatioOptionsForActiveModel() as $rValue => $rLabel)
                                <option value="{{ $rValue }}">{{ $rLabel }}</option>
                            @endforeach
                        </x-forms.input>

                        <p
                            class="m-0 text-2xs opacity-60"
                            x-show="!generating"
                        >
                            <span x-text="selectedTemplateSlugs.length"></span>
                            {{ __('of') }}
                            <span x-text="maxTemplates"></span>
                            {{ __('templates selected') }}
                        </p>

                        <div
                            x-data
                            x-init="$nextTick(() => $dispatch('generator-changed', { generator: '{{ \App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry::getDefaultModel()->value }}', quantity: 1 }))"
                        >
                            <x-cost-preview class="w-full justify-end" />
                        </div>

                        <x-button
                            class="w-full"
                            size="xl"
                            type="submit"
                            variant="primary"
                            ::disabled="generating || !productImage || selectedTemplateSlugs.length === 0"
                        >
                            <span x-show="!generating">{{ __('Generate') }}</span>
                            <span
                                class="flex items-center gap-2"
                                x-show="generating"
                                x-cloak
                            >
                                <x-tabler-loader-2 class="size-4 animate-spin" />
                                {{ __('Generating...') }}
                            </span>
                        </x-button>
                </form>
            </div>

            {{-- Right: Pick a Template / Results --}}
            <div class="flex w-full flex-col lg:basis-2/3">
                <div class="mb-5 flex flex-wrap justify-between gap-3 border-b py-2.5 transition-border">
                    <p class="m-0 text-[12px] font-semibold">
                        <span x-show="!results.length && !generating">{{ __('Pick a Template') }}</span>
                        <span
                            x-show="results.length || generating"
                            x-cloak
                        >{{ __('Generated Results') }}</span>
                    </p>
                    <x-button
                        class="text-[12px] font-semibold"
                        variant="link"
                        @click="resetResults"
                        x-show="results.length"
                        x-cloak
                    >
                        <x-tabler-chevron-left class="size-5" />
                        {{ __('Back to Templates') }}
                    </x-button>
                </div>

                {{-- Templates Grid --}}
                <div
                    class="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3"
                    x-show="!results.length && !generating"
                >
                    <template
                        x-for="tpl in templates"
                        :key="tpl.slug"
                    >
                        <div
                            class="group relative cursor-pointer rounded-lg border p-2 transition-all hover:-translate-y-1 [&.selected]:border-primary [&.selected]:ring-2 [&.selected]:ring-primary"
                            :class="{ 'selected': isTemplateSelected(tpl.slug) }"
                            @click="toggleTemplate(tpl.slug)"
                        >
                            <div class="relative overflow-hidden rounded">
                                <div
                                    class="absolute end-2 top-2 z-10 inline-grid size-7 place-items-center rounded-full bg-secondary text-secondary-foreground shadow-lg shadow-black/5"
                                    x-show="isTemplateSelected(tpl.slug)"
                                    x-cloak
                                >
                                    <x-tabler-check class="size-4" />
                                </div>

                                <img
                                    class="aspect-square w-full object-cover"
                                    :src="`{{ asset('') }}${tpl.image}`"
                                    :alt="tpl.label"
                                    loading="lazy"
                                >
                            </div>

                            <div class="mt-2 flex items-end justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="m-0 truncate text-3xs font-medium opacity-60"
                                        x-text="tpl.category"
                                    ></p>
                                    <p
                                        class="m-0 truncate text-2xs font-semibold"
                                        x-text="tpl.label"
                                    ></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Loading Skeleton --}}
                <div
                    class="lqd-loading-skeleton lqd-is-loading grid grid-cols-1 gap-4 sm:grid-cols-2"
                    x-cloak
                    x-show="generating"
                >
                    <template
                        x-for="n in (selectedTemplateSlugs.length || 1)"
                        :key="`tpl-skeleton-${n}`"
                    >
                        <div
                            class="aspect-[3/4] w-full rounded"
                            data-lqd-skeleton-el
                        ></div>
                    </template>
                </div>

                {{-- Results --}}
                <div
                    class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2"
                    x-show="results.length && !generating"
                    x-cloak
                >
                    <template
                        x-for="(result, index) in results"
                        :key="result.id || index"
                    >
                        <div class="group relative overflow-hidden rounded-lg">
                            <img
                                class="w-full rounded-lg"
                                :src="result.thumbnail || result.image_url"
                                :alt="'Generated ' + (index + 1)"
                                loading="lazy"
                            >

                            {{-- Hover Overlay --}}
                            <div class="absolute inset-0 flex items-center justify-center rounded-lg bg-black/10 opacity-0 transition-opacity group-hover:opacity-100">
                                <div class="flex gap-2">
                                    <x-button
                                        class="inline-grid size-9 place-items-center bg-white p-0 text-black hover:bg-white hover:text-black"
                                        data-fslightbox="aps-results"
                                        size="none"
                                        ::href="result.image_url"
                                        title="{{ __('View') }}"
                                    >
                                        <x-tabler-eye class="size-4" />
                                    </x-button>
                                    <x-button
                                        class="inline-grid size-9 place-items-center bg-white p-0 text-black hover:bg-white hover:text-black"
                                        size="none"
                                        ::href="result.image_url"
                                        ::download="`image_${result.id}.jpg`"
                                        title="{{ __('Download') }}"
                                    >
                                        <x-tabler-download class="size-4" />
                                    </x-button>
                                </div>
                            </div>

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
                                                    :href="result.image_url"
                                                    :download="`image_${result.id}.jpg`"
                                                    @click="toggle('collapse')"
                                                >
                                                    <x-tabler-download class="me-2 size-5" />
                                                    {{ __('Download') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a
                                                    class="text-heading-foreground/2 flex px-5 py-2 transition-colors hover:bg-heading-foreground/[3%]"
                                                    href="javascript:void(0);"
                                                    @click.prevent="editResult(result); toggle('collapse')"
                                                >
                                                    <x-tabler-scissors class="me-2 size-5" />
                                                    {{ __('Edit') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a
                                                    class="text-heading-foreground/2 flex px-5 py-2 transition-colors hover:bg-heading-foreground/[3%]"
                                                    href="javascript:void(0);"
                                                    @click.prevent="videoResult(result); toggle('collapse')"
                                                >
                                                    <x-tabler-video class="me-2 size-5" />
                                                    {{ __('Create Video') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </x-slot:dropdown>
                                </x-dropdown.dropdown>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection

@include('ai-photoshoot::templates.scripts')
