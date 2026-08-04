<header class="titan-shell-header">
    <div class="titan-shell-header__row">
        <button x-ref="titanDrawerTrigger" class="titan-shell-icon-button" type="button" @click.prevent="openTitanDrawer" :aria-expanded="titanDrawerOpen.toString()" aria-controls="titan-app-drawer" aria-label="{{ __('Open app menu') }}">☰</button>
        <div class="titan-shell-header__title">
            <strong>{{ $titanShell['name'] }}</strong>
            <span x-text="titanViewLabel"></span>
        </div>
        <div class="titan-shell-header__actions">
            <button class="titan-shell-sync" type="button" @click="navigateTitanView('offline-queue')" :data-state="titanConnectionState">
                <span aria-hidden="true">●</span><span x-text="titanConnectionLabel"></span>
            </button>
            <button class="titan-shell-icon-button" type="button" @click="navigateTitanView('notifications')" aria-label="{{ __('Notifications') }}">♢</button>
            <button x-ref="titanSettingsTrigger" class="titan-shell-icon-button" type="button" @click.prevent="openTitanSettings" :aria-expanded="titanSettingsOpen.toString()" aria-controls="titan-settings-panel" aria-label="{{ __('Open settings') }}">⚙</button>
        </div>
    </div>
    <button class="titan-shell-chat-launcher" type="button" @click.prevent="openTitanChat">
        <span x-text="titanChatPlaceholder"></span>
        <span class="titan-shell-chat-launcher__actions" aria-hidden="true">＋　🎙　➤</span>
    </button>
</header>
