@props([
    'cards' => [],
])

<div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-6">
    @foreach ($cards as $card)
        <x-card
            class="text-center transition hover:-translate-y-1"
            wire:key="aps-flow-card-{{ $card['key'] ?? $loop->index }}"
        >
            @if (! empty($card['image']))
                <a href="{{ $card['route'] }}" class="block">
                    <img
                        class="mb-4 aspect-[4/3] w-full rounded-lg object-contain object-center"
                        aria-hidden="true"
                        alt=""
                        src="{{ $card['image'] }}"
                    >
                </a>
            @endif

            <p class="mb-4 text-sm font-medium leading-snug">
                {{ $card['description'] }}
            </p>

            <x-button
                href="{{ $card['route'] }}"
                variant="ghost-shadow"
                class="mx-auto"
            >
                <x-tabler-plus class="size-4" />
                {{ $card['cta'] ?? __('Generate Now') }}
            </x-button>
        </x-card>
    @endforeach
</div>
