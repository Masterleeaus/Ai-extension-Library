<div
    class="lqd-cs-annotations-popup fixed z-20 w-72 max-w-[calc(100vw-1.5rem)] rounded-xl bg-background/90 p-2 shadow-lg shadow-black/15 ring-1 ring-foreground/10 backdrop-blur-lg"
    x-cloak
    x-show="annotationPopup && annotationMode && currentView === 'editor'"
    :style="annotationPopup && {
        top: annotationPopup.y + 'px',
        left: annotationPopup.x + 'px',
    }"
    @click.outside="annotationPopup && activePinId && confirmActivePin()"
>
    {{-- Per-pin prompt (comment + replace-text tools) --}}
    <template x-if="activePinId">
        <div>
            <x-forms.input
                type="textarea"
                rows="3"
                placeholder="{{ __('What should change here?') }}"
                ::placeholder="activePin()?.annotation?.tool === 'replace-text' ? '{{ __('Edit the detected text') }}' : '{{ __('What should change here?') }}'"
                x-ref="annotationPromptInput"
                ::value="activePin()?.mark?.prompt ?? ''"
                @input="setActivePinPrompt($event.target.value)"
                @keydown.escape.prevent="confirmActivePin()"
                @keydown.enter.prevent="!$event.shiftKey && confirmActivePin()"
            />

            <div class="mt-2 flex items-center justify-end gap-2">
                <x-button
                    class="px-2.5 py-1 text-xs"
                    variant="link"
                    type="button"
                    @click.prevent="removeActivePin()"
                >
                    {{ __('Delete') }}
                </x-button>
                <x-button
                    class="px-3 py-1 text-xs"
                    variant="primary"
                    type="button"
                    @click.prevent="confirmActivePin()"
                >
                    {{ __('Save') }}
                    <x-tabler-check class="size-4" />
                </x-button>
            </div>
        </div>
    </template>

    {{-- Shared prompt (rect / oval / lasso / brush) --}}
    <template x-if="!activePinId">
        <div>
            <x-forms.input
                type="textarea"
                rows="3"
                placeholder="{{ __('Describe the change you want for this region…') }}"
                x-ref="annotationPromptInput"
                ::value="activeAnnotation()?.prompt ?? ''"
                @input="setActiveAnnotationPrompt($event.target.value)"
                @keydown.escape.prevent="cancelActiveAnnotation()"
                @keydown.enter.prevent="!$event.shiftKey && submitActiveAnnotation()"
                ::disabled="annotationSubmitting"
            />

            <div class="mt-2 flex items-center justify-end gap-2">
                <button
                    class="me-auto inline-grid size-7 place-items-center rounded-md text-foreground/60 transition hover:bg-foreground/5 hover:text-foreground"
                    type="button"
                    title="{{ __('Minimize') }}"
                    x-show="annotationTool === 'brush'"
                    @click.prevent="minimizePopup()"
                    ::disabled="annotationSubmitting"
                >
                    <x-tabler-minus class="size-4" />
                    <span class="sr-only">{{ __('Minimize') }}</span>
                </button>
                <x-button
                    class="px-3 py-1.5 text-xs"
                    variant="link"
                    type="button"
                    @click.prevent="cancelActiveAnnotation()"
                    ::disabled="annotationSubmitting"
                >
                    {{ __('Cancel') }}
                </x-button>
                <x-button
                    class="px-3 py-1.5 text-xs"
                    variant="primary"
                    type="button"
                    @click.prevent="submitActiveAnnotation()"
                    ::disabled="!activeAnnotation()?.prompt?.trim() || annotationSubmitting"
                >
                    <span x-show="!annotationSubmitting">{{ __('Send') }}</span>
                    <x-tabler-send-2
                        class="size-4"
                        x-show="!annotationSubmitting"
                    />
                    <span x-show="annotationSubmitting">{{ __('Working') }}</span>
                    <x-tabler-loader-2
                        class="size-4 animate-spin"
                        x-show="annotationSubmitting"
                    />
                </x-button>
            </div>
        </div>
    </template>
</div>
