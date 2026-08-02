<div class="titan-shell-overlay" x-cloak x-show="titanSettingsOpen" x-transition.opacity @click="closeTitanSettings" aria-hidden="true"></div>
<aside id="titan-settings-panel" x-ref="titanSettingsPanel" tabindex="-1" class="titan-shell-settings" x-cloak x-show="titanSettingsOpen" role="dialog" aria-modal="true" aria-label="{{ __('Settings') }}" @keydown.escape.window="closeTitanSettings">
    <header class="titan-shell-settings__header">
        <button x-show="titanActiveSetting" type="button" class="titan-shell-icon-button" @click="titanActiveSetting=null" aria-label="{{ __('Back to settings') }}">←</button>
        <div><strong x-text="titanActiveSettingLabel || '{{ __('Settings') }}'"></strong><p class="m-0 text-xs opacity-70">{{ $titanShell['name'] }}</p></div>
        <button type="button" class="titan-shell-icon-button" @click="closeTitanSettings" aria-label="{{ __('Close settings') }}">×</button>
    </header>

    <div class="titan-shell-settings__links" x-show="!titanActiveSetting">
        @foreach ($titanShell['settings'] as $id => $label)
            <button type="button" @click="openTitanSetting('{{ $id }}', '{{ addslashes(__($label)) }}')">
                <span>{{ __($label) }}</span><span class="text-xs opacity-60" x-text="getTitanSettingStatus('{{ $id }}')"></span>
            </button>
        @endforeach
    </div>

    <div class="titan-setting-page" x-show="titanActiveSetting">
        <section x-show="titanActiveSetting==='ai-providers'">
            <h3>{{ __('Processing mode') }}</h3>
            <select x-model="titanSettings.processing_mode" @change="saveTitanSettings"><option value="device-first">Device first</option><option value="private-hybrid">Private hybrid</option><option value="company-ai">Company AI</option><option value="cloud-preferred">Cloud preferred</option><option value="offline-only">Offline only</option></select>
            <h3>{{ __('BYO provider') }}</h3>
            <label>Provider<select x-model="titanProvider.id"><option value="openai">OpenAI</option><option value="anthropic">Anthropic</option><option value="gemini">Google Gemini</option><option value="openrouter">OpenRouter</option><option value="ollama">Ollama / local</option><option value="compatible">OpenAI-compatible</option></select></label>
            <label>API key<input type="password" x-model="titanProvider.api_key" autocomplete="new-password" placeholder="Stored encrypted on this device"></label>
            <label>Model<input type="text" x-model="titanProvider.model" placeholder="Optional default model"></label>
            <label>Base URL<input type="url" x-model="titanProvider.base_url" placeholder="Optional custom endpoint"></label>
            <button class="titan-setting-primary" type="button" @click="saveTitanProvider">{{ __('Encrypt and connect') }}</button>
            <p class="titan-setting-note" x-text="titanSettingMessage"></p>
        </section>

        <section x-show="titanActiveSetting==='privacy'">
            <h3>{{ __('Privacy mode') }}</h3>
            <select x-model="titanSettings.privacy_mode" @change="saveTitanSettings"><option value="strict-device">Strict device-only</option><option value="device-first-hybrid">Device-first hybrid</option><option value="company-controlled">Company-controlled</option><option value="cloud-assisted">Cloud-assisted</option></select>
            <label class="titan-setting-toggle"><input type="checkbox" x-model="titanSettings.redact_personal" @change="saveTitanSettings"> Redact personal data before cloud use</label>
            <label class="titan-setting-toggle"><input type="checkbox" x-model="titanSettings.ask_before_attachments" @change="saveTitanSettings"> Ask before sending attachments</label>
            <button type="button" @click="exportTitanSettings">{{ __('Export local settings') }}</button>
        </section>

        <section x-show="titanActiveSetting==='device-security'">
            <div class="titan-setting-status"><span>{{ __('Device vault') }}</span><strong x-text="titanRuntimeStatus.vault"></strong></div>
            <label>{{ __('Vault passphrase') }}<input type="password" x-model="titanVaultPassphrase" autocomplete="current-password"></label>
            <div class="titan-setting-actions"><button class="titan-setting-primary" @click="unlockTitanVault">{{ __('Create / unlock') }}</button><button @click="lockTitanVault">{{ __('Lock now') }}</button></div>
            <p class="titan-setting-note">Provider keys are encrypted with AES-256-GCM and are never shown after saving.</p>
        </section>

        <section x-show="titanActiveSetting==='offline-sync'">
            <div class="titan-setting-status"><span>{{ __('Connection') }}</span><strong x-text="titanRuntimeStatus.connection"></strong></div>
            <div class="titan-setting-status"><span>{{ __('Pending') }}</span><strong x-text="titanRuntimeStatus.pending"></strong></div>
            <div class="titan-setting-actions"><button class="titan-setting-primary" @click="syncTitanNow">{{ __('Sync now') }}</button><button @click="openTitanQueue">{{ __('Open queue') }}</button></div>
            <label class="titan-setting-toggle"><input type="checkbox" x-model="titanSettings.wifi_uploads" @change="saveTitanSettings"> Wi-Fi-only attachment uploads</label>
            <label class="titan-setting-toggle"><input type="checkbox" x-model="titanSettings.auto_download_jobs" @change="saveTitanSettings"> Auto-download assigned jobs</label>
        </section>

        <section x-show="titanActiveSetting==='workcore'">
            <div class="titan-setting-status"><span>{{ __('WorkCore') }}</span><strong x-text="titanRuntimeStatus.workcore"></strong></div>
            <p class="titan-setting-note">WorkCore remains authoritative for permissions, finance, scheduling, compliance and final conflict resolution.</p>
            <button @click="testTitanWorkCore">{{ __('Test connection') }}</button><pre x-text="titanDiagnosticSummary"></pre>
        </section>

        <section x-show="titanActiveSetting==='permissions'">
            <template x-for="(supported, capability) in titanCapabilities" :key="capability"><div class="titan-setting-status"><span x-text="capability"></span><strong x-text="supported ? 'Available' : 'Unavailable'"></strong></div></template>
        </section>

        <section x-show="titanActiveSetting==='notifications'">
            <label class="titan-setting-toggle"><input type="checkbox" x-model="titanSettings.notifications" @change="saveTitanSettings"> Enable notifications</label>
            <label class="titan-setting-toggle"><input type="checkbox" x-model="titanSettings.quiet_hours" @change="saveTitanSettings"> Quiet hours</label>
            <button @click="requestTitanNotifications">{{ __('Request notification permission') }}</button>
        </section>

        <section x-show="titanActiveSetting==='channels'"><p class="titan-setting-note">PWA, website, email, SMS, WhatsApp, Telegram, Messenger, Instagram and voice channel connections remain company-controlled.</p></section>

        <section x-show="titanActiveSetting==='appearance'">
            <label>Theme<select x-model="titanSettings.theme" @change="saveTitanSettings"><option value="system">System</option><option value="light">Light</option><option value="dark">Dark</option></select></label>
            <label>Text size<select x-model="titanSettings.text_size" @change="saveTitanSettings"><option value="normal">Normal</option><option value="large">Large</option><option value="extra-large">Extra large</option></select></label>
            <label class="titan-setting-toggle"><input type="checkbox" x-model="titanSettings.reduced_motion" @change="saveTitanSettings"> Reduced motion</label>
        </section>

        <section x-show="titanActiveSetting==='accessibility'"><label>Language<select x-model="titanSettings.language" @change="saveTitanSettings"><option value="en-AU">English (Australia)</option><option value="en-US">English (US)</option><option value="en-GB">English (UK)</option></select></label><p class="titan-setting-note">The shell supports keyboard navigation, reduced motion and larger text preferences.</p></section>

        <section x-show="titanActiveSetting==='diagnostics'">
            <button class="titan-setting-primary" @click="runTitanDiagnostics">{{ __('Run self-test') }}</button>
            <pre x-text="titanDiagnosticSummary"></pre>
        </section>
    </div>
</aside>
