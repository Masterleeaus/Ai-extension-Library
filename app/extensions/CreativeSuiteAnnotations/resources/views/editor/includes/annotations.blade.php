@php
    $tools = [
        'comment' => [
            'label' => __('Comment'),
            'icon' => 'message-circle',
        ],
        'rect' => [
            'label' => __('Rectangle'),
            'icon' => 'square',
        ],
        'oval' => [
            'label' => __('Oval'),
            'icon' => 'circle',
        ],
        'lasso' => [
            'label' => __('Lasso'),
            'icon' => 'lasso',
        ],
        'brush' => [
            'label' => __('Brush'),
            'icon' => 'brush',
        ],
        'replace-text' => [
            'label' => __('Replace Text'),
            'icon' => 'text-recognition',
        ],
    ];
@endphp

<div
    class="lqd-cs-annotations-bar fixed bottom-9 start-1/2 z-10 flex -translate-x-1/2 gap-1 rounded-full bg-background/90 p-2 shadow-lg shadow-black/15 backdrop-blur-md max-lg:bottom-[--sidebar-h] max-lg:end-0 max-lg:start-0 max-lg:w-full max-lg:translate-x-0 max-lg:overflow-x-auto max-lg:rounded-none max-lg:border-b max-lg:shadow-none"
    x-ref="annotationsbar"
    x-cloak
    x-show="annotationMode && currentView === 'editor'"
>
    <x-forms.input
        class:container="size-10 shrink-0"
        class="inline-grid size-full cursor-pointer place-items-center !rounded-full border-0 bg-transparent p-0 shadow-none [&_.lqd-input-color-wrap]:inline-grid [&_.lqd-input-color-wrap]:!size-7 [&_.lqd-input-color-wrap]:place-items-center [&_.lqd-input-color-wrap]:rounded-full [&_.lqd-input-color-wrap]:!border-2 [&_.lqd-input-color-wrap]:!p-1.5"
        class:input="!hidden"
        class:clear-btn="!hidden"
        type="color"
        value="#ff3366"
        x-model="annotationColor"
        title="{{ __('Annotation Color') }}"
    />

    @foreach ($tools as $key => $tool)
        <button
            class="inline-grid size-10 shrink-0 place-items-center rounded-lg transition hover:scale-105 hover:bg-foreground/5 active:scale-95 disabled:pointer-events-none disabled:opacity-40 lg:before:pointer-events-none lg:before:absolute lg:before:bottom-full lg:before:left-1/2 lg:before:mb-1 lg:before:-translate-x-1/2 lg:before:translate-y-0.5 lg:before:whitespace-nowrap lg:before:rounded lg:before:bg-background lg:before:px-2.5 lg:before:py-1 lg:before:text-2xs lg:before:font-medium lg:before:opacity-0 lg:before:shadow-md lg:before:shadow-black/5 lg:before:transition lg:before:content-[attr(data-label)] lg:hover:before:translate-y-0 lg:hover:before:opacity-100 [&.active]:bg-primary/10 [&.active]:text-primary"
            data-label="{{ $tool['label'] }}"
            type="button"
            :class="{ active: annotationTool === '{{ $key }}' }"
            ::disabled="annotationSubmitting"
            @click.prevent="annotationTool = annotationTool === '{{ $key }}' ? null : '{{ $key }}'"
        >
            <x-dynamic-component :component="'tabler-' . $tool['icon']" />
            <span class="sr-only">
                {{ $tool['label'] }}
            </span>
        </button>
    @endforeach

    <div
        class="mx-1 h-6 w-px shrink-0 self-center bg-foreground/10"
        x-cloak
        x-show="canUndoAnnotation() || canRedoAnnotation()"
    ></div>

    <button
        class="inline-grid size-10 shrink-0 place-items-center rounded-lg transition hover:bg-foreground/5"
        type="button"
        title="{{ __('Undo') }}"
        x-cloak
        x-show="canUndoAnnotation()"
        @click.prevent="undoAnnotation()"
    >
        <x-tabler-arrow-back-up class="size-4" />
        <span class="sr-only">{{ __('Undo') }}</span>
    </button>
    <button
        class="inline-grid size-10 shrink-0 place-items-center rounded-lg transition hover:bg-foreground/5"
        type="button"
        title="{{ __('Redo') }}"
        x-cloak
        x-show="canRedoAnnotation()"
        @click.prevent="redoAnnotation()"
    >
        <x-tabler-arrow-forward-up class="size-4" />
        <span class="sr-only">{{ __('Redo') }}</span>
    </button>

    <div
        class="flex shrink-0 items-center gap-0.5 rounded-lg bg-foreground/[3%] px-1.5"
        x-cloak
        x-show="annotationTool === 'brush'"
    >
        <button
            class="inline-flex size-7 items-center justify-center rounded-lg transition hover:bg-foreground/5"
            type="button"
            title="{{ __('Decrease brush size') }}"
            @click="annotationBrushSize = Math.max(10, annotationBrushSize - 10)"
        >
            <x-tabler-minus class="size-4" />
        </button>
        <span
            class="inline-flex min-w-[3ch] items-center justify-center text-2xs font-medium tabular-nums"
            x-text="annotationBrushSize"
        ></span>
        <button
            class="inline-flex size-7 items-center justify-center rounded-lg transition hover:bg-foreground/5"
            type="button"
            title="{{ __('Increase brush size') }}"
            @click="annotationBrushSize = Math.min(150, annotationBrushSize + 10)"
        >
            <x-tabler-plus class="size-4" />
        </button>
    </div>

    <x-button
        class="shrink-0 px-4"
        variant="primary"
        type="button"
        x-cloak
        x-show="annotationTool === 'comment' && hasPendingAnnotation()"
        ::disabled="!canSubmitPending() || annotationSubmitting"
        @click.prevent="completePendingAnnotation()"
    >
        <span x-show="!annotationSubmitting">{{ __('Submit') }}</span>
        <span x-show="annotationSubmitting">{{ __('Working') }}</span>
    </x-button>

    {{-- Replace Text tool: Analyze / Re-analyze + Submit --}}
    <x-button
        class="shrink-0 px-4"
        variant="primary"
        type="button"
        x-cloak
        x-show="annotationTool === 'replace-text' && !hasPendingAnnotation()"
        ::disabled="annotationAnalyzing || annotationSubmitting"
        @click.prevent="analyzeImage()"
    >
        <span x-show="!annotationAnalyzing">{{ __('Analyze Image') }}</span>
        <span x-show="annotationAnalyzing">{{ __('Analyzing') }}</span>
    </x-button>
    <x-button
        class="shrink-0 px-3"
        variant="link"
        type="button"
        x-cloak
        x-show="annotationTool === 'replace-text' && hasPendingAnnotation()"
        ::disabled="annotationAnalyzing || annotationSubmitting"
        @click.prevent="analyzeImage()"
    >
        <x-tabler-refresh class="size-4" />
        <span class="ms-1.5">{{ __('Re-analyze') }}</span>
    </x-button>
    <x-button
        class="shrink-0 px-4"
        variant="primary"
        type="button"
        x-cloak
        x-show="annotationTool === 'replace-text' && hasPendingAnnotation()"
        ::disabled="!canSubmitReplaceText() || annotationSubmitting"
        @click.prevent="completePendingAnnotation()"
    >
        <span x-show="!annotationSubmitting">{{ __('Submit') }}</span>
        <span x-show="annotationSubmitting">{{ __('Working') }}</span>
    </x-button>

    <x-button
        class="shrink-0 px-4"
        variant="primary"
        type="button"
        x-cloak
        x-show="canMaximizePopup()"
        @click.prevent="maximizePopup()"
    >
        {{ __('Prompt') }}
    </x-button>

    <x-button
        class="ms-auto shrink-0 px-4"
        variant="link"
        type="button"
        @click.prevent="exitAnnotationMode()"
    >
        {{ __('Done') }}
    </x-button>

    <button
        class="relative hidden size-10 shrink-0 place-items-center rounded-lg text-foreground/40 transition hover:text-foreground lg:inline-grid lg:before:pointer-events-none lg:before:absolute lg:before:bottom-full lg:before:right-0 lg:before:mb-2 lg:before:w-60 lg:before:whitespace-normal lg:before:rounded-lg lg:before:bg-background lg:before:p-3 lg:before:text-start lg:before:text-2xs lg:before:font-medium lg:before:leading-relaxed lg:before:opacity-0 lg:before:shadow-lg lg:before:shadow-black/10 lg:before:ring-1 lg:before:ring-foreground/10 lg:before:transition lg:before:content-[attr(data-label)] lg:hover:before:opacity-100"
        data-label="{{ __('Tip: Annotations mark where to edit — your prompt still needs to describe what to change. Be specific for the best results.') }}"
        type="button"
        tabindex="-1"
        title="{{ __('Tip: Annotations mark where to edit — your prompt still needs to describe what to change. Be specific for the best results.') }}"
    >
        <x-tabler-info-circle class="size-4" />
        <span class="sr-only">{{ __('Prompting tip') }}</span>
    </button>
</div>

@includeIf('creative-suite-annotations::editor.includes.popup')

@pushOnce('script-before')
    @vite('app/Extensions/CreativeSuiteAnnotations/resources/assets/js/creative-suite-annotations.js')
@endPushOnce
