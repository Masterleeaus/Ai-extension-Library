<section class="hidden h-[calc(100vh-49px)]" data-team-channel-panel data-chatbot-workspace="channels">
    <header class="flex items-center justify-between gap-3 border-b p-4">
        <div><h3 class="font-semibold">{{ __('Channels') }}</h3><p class="text-xs opacity-70">{{ __('Public, private and business-linked rooms') }}</p></div>
        <div class="flex gap-2"><button type="button" class="lqd-btn lqd-btn-ghost" data-team-channel-discover>{{ __('Discover') }}</button><button type="button" class="lqd-btn lqd-btn-primary" data-team-channel-new>{{ __('New channel') }}</button></div>
    </header>
    <div class="grid min-h-[420px] grid-cols-12">
        <aside class="col-span-4 overflow-y-auto border-r p-3" data-team-channel-list></aside>
        <section class="col-span-8 p-4" data-team-channel-thread><p class="text-sm opacity-70">{{ __('Select a channel or linked room.') }}</p></section>
    </div>
</section>
