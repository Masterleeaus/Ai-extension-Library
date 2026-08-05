<div class="lqd-social-media-cards-grid mt-10 flex justify-between gap-5">
    @foreach ($platforms as $platform)
        @php
            $image = 'vendor/social-media/icons/' . $platform->value . '.svg';
            $image_dark_version = 'vendor/social-media/icons/' . $platform->value . '-light.svg';
            $image_exists = file_exists(public_path($image));
            $image_dark_exists = file_exists(public_path($image_dark_version));
        @endphp
        <x-card
            class="lqd-social-media-card flex flex-col text-heading-foreground transition-all hover:scale-105 hover:border-heading-foreground/10 hover:shadow-lg hover:shadow-black/5"
            class:body="flex flex-col "
        >
            <figure class="mb-8 flex h-9 w-9 items-center justify-center overflow-hidden rounded-lg bg-foreground/5 text-xs font-semibold transition-all group-hover/card:scale-125">
                @if ($image_exists)
                    <img
                        @class([
                            'w-full h-auto',
                            'dark:hidden' => $image_dark_exists,
                        ])
                        src="{{ asset($image) }}"
                        alt="{{ $platform->label() }}"
                    />
                    @if ($image_dark_exists)
                        <img
                            class="hidden h-auto w-full dark:block"
                            src="{{ asset($image_dark_version) }}"
                            alt="{{ $platform->label() }}"
                        />
                    @endif
                @else
                    <span aria-hidden="true">{{ str($platform->label())->substr(0, 2)->upper() }}</span>
                @endif
            </figure>
            <h4 class="mb-2 text-lg text-inherit">
                {{ $platform->label() }}
            </h4>
            <a
                class="relative opacity-70"
                target="_blank"
                href="{{ route('social-media.oauth.connect.' . $platform->value) }}"
            >@lang('Add New Account')</a>

            <a
                class="absolute inset-0 z-2 inline-block"
                target="_blank"
                href="{{ route('social-media.oauth.connect.' . $platform->value) }}"
            ></a>
        </x-card>
    @endforeach
</div>
