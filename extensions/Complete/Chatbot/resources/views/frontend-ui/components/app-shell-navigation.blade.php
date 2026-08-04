<nav class="titan-shell-nav" aria-label="{{ __('Primary app navigation') }}">
    @foreach ($titanShell['primary'] as $item)
        <button
            class="titan-shell-nav__item"
            type="button"
            :class="{ 'is-active': titanView === '{{ $item['id'] }}' }"
            :aria-current="titanView === '{{ $item['id'] }}' ? 'page' : null"
            @click.prevent="navigateTitanView('{{ $item['id'] }}')"
            aria-label="{{ __($item['label']) }}"
        >
            <span class="titan-shell-nav__icon" aria-hidden="true">{{ mb_substr($item['label'], 0, 1) }}</span>
            <span>{{ __($item['label']) }}</span>
            <span class="titan-shell-nav__badge" x-show="titanBadges['{{ $item['id'] }}']" x-text="titanBadges['{{ $item['id'] }}']"></span>
        </button>
    @endforeach
</nav>
