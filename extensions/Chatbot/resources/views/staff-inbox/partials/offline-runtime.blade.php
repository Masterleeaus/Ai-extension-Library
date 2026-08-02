<div class="chatbot-staff-offline-state" aria-live="polite">
    <span data-chatbot-connectivity>{{ __('Online') }}</span>
    <span><strong data-chatbot-pending-count>0</strong> {{ __('pending') }}</span>
    <span>{{ __('Last sync:') }} <span data-chatbot-last-sync>{{ __('Never') }}</span></span>
</div>
<script>
window.TitanChatbotContext = Object.assign(window.TitanChatbotContext || {}, {
    tenant_id: @json(auth()->user()?->team_id ?? auth()->id()),
    user_id: @json(auth()->id()),
    device_id: localStorage.getItem('titan-chatbot-device-id') || (() => {
        const id = crypto.randomUUID(); localStorage.setItem('titan-chatbot-device-id', id); return id;
    })()
});
</script>
<script defer src="{{ asset('chatbot-pwa/device-db.js') }}"></script>
<script defer src="{{ asset('chatbot-pwa/crypto-vault.js') }}"></script>
<script defer src="{{ asset('chatbot-pwa/offline-store.js') }}"></script>
<script defer src="{{ asset('chatbot-pwa/local-repositories.js') }}"></script>
<script defer src="{{ asset('chatbot-pwa/outbox.js') }}"></script>
<script defer src="{{ asset('chatbot-pwa/local-services.js') }}"></script>
<script defer src="{{ asset('chatbot-pwa/local-search.js') }}"></script>
<script defer src="{{ asset('chatbot-pwa/local-ui-bridge.js') }}"></script>
