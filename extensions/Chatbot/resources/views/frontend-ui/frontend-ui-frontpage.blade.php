@php
    $chatbotData = isset($chatbot) ? (is_array($chatbot) ? $chatbot : $chatbot->toArray()) : [];
    $templateSlug = $chatbotData['titan_template'] ?? $chatbotData['template_slug'] ?? null;
    $titanShell = \App\Extensions\Chatbot\System\TitanShell\TemplateNavigation::resolve($templateSlug);
@endphp

@vite('app/Extensions/Chatbot/resources/assets/scss/external-chatbot.scss')
@vite('app/Extensions/Chatbot/resources/assets/scss/external-chatbot-tw.scss')
<link rel="stylesheet" href="{{ asset('vendor/chatbot/css/titan-generative-ui.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/chatbot/css/titan-app-shell.css') }}">

@php
    $style = '';

    if (!empty($chatbot['color'])) {
        $style .= '--lqd-ext-chat-primary: ' . $chatbot['color'] . ';';
    }
@endphp

<div
    class="lqd-ext-chatbot"
    data-pos-x="{{ $chatbot['position'] ?? 'right' }}"
    data-pos-y="{{ $chatbot['position_y'] ?? 'bottom' }}"
    data-window-state="{{ $is_iframe ? 'open' : 'close' }}"
    data-embedded="true"
    data-fetching="true"
    data-titan-shell="{{ $titanShell['slug'] }}"
    x-data="externalChatbot"
    :data-fetching="fetching ? 'true' : 'false'"
    @if ($style) style="{{ $style }}" @endif
>
    <div
        class="lqd-ext-chatbot-window before:pointer-events-none before:absolute before:bottom-0 before:z-3 before:h-40 before:w-full before:bg-gradient-to-t before:from-[--lqd-ext-chat-window-bg] before:from-40% before:to-transparent before:to-85%">
        @include('chatbot::frontend-ui.components.header')

        <div class="lqd-ext-chatbot-window-contents-wrap grid grow place-items-start overflow-hidden">
            @include('chatbot::frontend-ui.components.operational-workspace')
            @include('chatbot::frontend-ui.views.welcome')
            @include('chatbot::frontend-ui.views.routes')
            @include('chatbot::frontend-ui.components.loader')
        </div>

        @include('chatbot::frontend-ui.components.floating-bar')

        @include('chatbot::frontend-ui.components.app-drawer')
        @include('chatbot::frontend-ui.components.settings-panel')
    </div>

    @include('chatbot::frontend-ui.components.trigger-button')
</div>

<link
    rel="stylesheet"
    href="{{ custom_theme_url('/assets/libs/prism/prism.css') }}"
/>
<link
    rel="stylesheet"
    href="{{ custom_theme_url('/assets/libs/picmo/picmo.min.css') }}"
/>
<script src="{{ custom_theme_url('/assets/libs/prism/prism.js') }}"></script>
<script src="{{ custom_theme_url('/assets/libs/beautify-html.min.js') }}"></script>
<script src="{{ custom_theme_url('/assets/libs/markdownit/markdown-it.min.js') }}"></script>
<script src="{{ custom_theme_url('/assets/libs/turndown.js') }}"></script>
<script src="{{ custom_theme_url('/assets/libs/picmo/picmo.min.js') }}"></script>
<script defer src="{{ asset('vendor/chatbot/js/titan-generative-ui.js') }}"></script>
<script defer src="{{ asset('vendor/chatbot/js/titan-operational-screens.js') }}"></script>
<script
    defer
    src="{{ asset('vendor/chatbot/js/alpine.min.js') }}"
></script>

@if (true)
    <script
        src="https://cdn.ably.com/lib/ably.min-1.js"
        type="text/javascript"
    ></script>
@endif

@include('chatbot::frontend-ui.frontend-ui-scripts', ['is_editor' => false])

{{-- Installable PWA support; isolated from builder code. --}}
@include('chatbot::frontend-ui.components.pwa')
