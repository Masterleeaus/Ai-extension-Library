@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('AI Photoshoots'))
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
@section('titlebar_subtitle', __('Produce studio-quality product photoshoots in seconds.'))

@php
    $cards = [
        [
            'key'         => 'template',
            'image'       => asset('vendor/ai-photoshoot/images/tools/template.png'),
            'description' => __('Place your existing product images in new scenes.'),
            'cta'         => __('Generate Now'),
            'route'       => route('dashboard.user.ai-photoshoot.templates.index'),
        ],
        [
            'key'         => 'custom',
            'image'       => asset('vendor/ai-photoshoot/images/tools/custom.png'),
            'description' => __('Create fully custom product photoshoots.'),
            'cta'         => __('Generate Now'),
            'route'       => route('dashboard.user.ai-photoshoot.custom.index'),
        ],
        [
            'key'         => 'replace-bg',
            'image'       => asset('vendor/ai-photoshoot/images/tools/replace-bg.png'),
            'description' => __('Replace the background of your product image.'),
            'cta'         => __('Generate Now'),
            'route'       => route('dashboard.user.ai-photoshoot.replace-background.index'),
        ],
    ];
@endphp

@section('content')
    <div
        class="py-10"
        x-data="{
            toolsSectionShowing: localStorage.getItem('aiPhotoStudioAdvancedSettings') === null ?
                true : localStorage.getItem('aiPhotoStudioAdvancedSettings') === 'true',

            toggleSettings() {
                this.toolsSectionShowing = !this.toolsSectionShowing;
                localStorage.setItem('aiPhotoStudioAdvancedSettings', this.toolsSectionShowing);
            }
        }"
    >
        <div class="space-y-12">
            <div>
                <div
                    class="flex flex-col gap-6"
                    x-show="toolsSectionShowing"
                    x-collapse
                >
                    @include('ai-photoshoot::components.flow-cards', ['cards' => $cards])
                </div>

                <x-button
                    class="mt-6 w-full gap-9"
                    variant="link"
                    @click.prevent="toggleSettings()"
                >
                    <span class="h-px grow bg-foreground/10"></span>
                    <span class="flex items-center gap-2">
                        <span x-text="toolsSectionShowing ? '{{ __('Hide All Tools') }}' : '{{ __('Show All Tools') }}'"></span>
                        <x-tabler-chevron-up
                            class="size-4"
                            x-show="toolsSectionShowing"
                        />
                        <x-tabler-chevron-down
                            class="size-4"
                            x-show="!toolsSectionShowing"
                        />
                    </span>
                    <span class="h-px grow bg-foreground/10"></span>
                </x-button>
            </div>

            @include('ai-photoshoot::components.latest-photoshoots')
        </div>
    </div>
@endsection
