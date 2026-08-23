@php
    $reachNavigation = [
        [
            'label' => __('Overview'),
            'key' => 'overview',
            'href' => route('dashboard.user.social-media.index'),
            'active' => request()->routeIs('dashboard.user.social-media.index'),
        ],
        [
            'label' => __('Create'),
            'key' => 'create',
            'href' => route('dashboard.user.social-media.workspace', ['section' => 'create']),
            'active' => ($section ?? null) === 'create',
        ],
        [
            'label' => __('Distribute'),
            'key' => 'distribute',
            'href' => route('dashboard.user.social-media.workspace', ['section' => 'distribute']),
            'active' => ($section ?? null) === 'distribute',
        ],
        [
            'label' => __('Listings'),
            'key' => 'listings',
            'href' => route('dashboard.user.social-media.workspace', ['section' => 'listings']),
            'active' => ($section ?? null) === 'listings',
        ],
        [
            'label' => __('Organic Social'),
            'key' => 'organic-social',
            'href' => route('dashboard.user.social-media.post.index'),
            'active' => request()->routeIs('dashboard.user.social-media.post.*'),
        ],
        [
            'label' => __('Paid Media'),
            'key' => 'paid-media',
            'href' => route('dashboard.user.social-media.workspace', ['section' => 'paid-media']),
            'active' => ($section ?? null) === 'paid-media',
        ],
        [
            'label' => __('Creative Studio'),
            'key' => 'creative-studio',
            'href' => route('dashboard.user.social-media.workspace', ['section' => 'creative-studio']),
            'active' => ($section ?? null) === 'creative-studio',
        ],
        [
            'label' => __('Catalogues'),
            'key' => 'catalogues',
            'href' => route('dashboard.user.social-media.workspace', ['section' => 'catalogues']),
            'active' => ($section ?? null) === 'catalogues',
        ],
        [
            'label' => __('Inbox'),
            'key' => 'inbox',
            'href' => route('dashboard.user.social-media.workspace', ['section' => 'inbox']),
            'active' => ($section ?? null) === 'inbox',
        ],
        [
            'label' => __('Connections'),
            'key' => 'connections',
            'href' => route('dashboard.user.social-media.platforms'),
            'active' => request()->routeIs('dashboard.user.social-media.platforms*'),
        ],
        [
            'label' => __('Analytics'),
            'key' => 'analytics',
            'href' => route('dashboard.user.social-media.workspace', ['section' => 'analytics']),
            'active' => ($section ?? null) === 'analytics',
        ],
        [
            'label' => __('Settings'),
            'key' => 'settings',
            'href' => route('dashboard.user.social-media.workspace', ['section' => 'settings']),
            'active' => ($section ?? null) === 'settings',
        ],
    ];
@endphp

<nav
    class="lqd-social-media-tools-grid-wrap mb-8"
    aria-label="{{ __('Titan Reach workspace') }}"
>
    <div class="no-scrollbar flex gap-2 overflow-x-auto pb-2 lg:flex-wrap">
        @foreach ($reachNavigation as $item)
            <x-button
                @class([
                    'shrink-0 rounded-xl text-xs font-medium',
                    'bg-primary text-primary-foreground outline-primary' => $item['active'],
                ])
                href="{{ $item['href'] }}"
                variant="{{ $item['active'] ? 'default' : 'outline' }}"
                aria-current="{{ $item['active'] ? 'page' : 'false' }}"
            >
                {{ $item['label'] }}
            </x-button>
        @endforeach
    </div>
</nav>
