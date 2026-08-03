<div class="titan-shell-overlay" x-cloak x-show="titanDrawerOpen" x-transition.opacity @click="closeTitanDrawer" aria-hidden="true"></div>
<aside
    id="titan-app-drawer"
    x-ref="titanDrawerPanel"
    tabindex="-1"
    class="titan-shell-drawer"
    x-cloak
    x-show="titanDrawerOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('App menu') }}"
    @keydown.escape.window="closeTitanDrawer"
>
    <header class="titan-shell-drawer__header">
        <div>
            <strong>{{ $titanShell['name'] }}</strong>
            <p class="m-0 text-xs opacity-70" x-text="titanConnectionLabel"></p>
        </div>
        <button type="button" class="titan-shell-icon-button" @click="closeTitanDrawer" aria-label="{{ __('Close menu') }}">×</button>
    </header>

    <nav class="titan-shell-drawer__links" aria-label="{{ __('Secondary navigation') }}">
        @foreach ($titanShell['drawer'] as $item)
            <button type="button" @click="navigateTitanView('{{ $item['id'] }}'); closeTitanDrawer()">
                <span>{{ __($item['label']) }}</span><span aria-hidden="true">›</span>
            </button>
        @endforeach
    </nav>

    <footer class="titan-shell-drawer__footer">
        <button type="button" @click="window.TitanWorkCore?.vault?.lock?.()">{{ __('Lock vault') }}</button>
        <button type="button" @click="window.dispatchEvent(new CustomEvent('titan:sign-out-requested'))">{{ __('Sign out') }}</button>
        <small>{{ __('Powered by') }} {{ $setting->site_name }}</small>
    </footer>
</aside>
