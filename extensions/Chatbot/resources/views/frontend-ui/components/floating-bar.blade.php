<div
    class="lqd-ext-chatbot-floating-bar absolute inset-x-[18px] bottom-14 z-10 grid rounded-full border px-3 py-1 shadow-2xl transition-all before:pointer-events-none before:absolute before:inset-0 before:-z-1 before:rounded-[inherit] before:backdrop-blur-md after:pointer-events-none after:absolute after:-inset-px after:rounded-[inherit] after:border after:border-black/5 after:opacity-0 after:transition-opacity after:[mask-image:linear-gradient(to_bottom,black,transparent)] sm:px-4"
    :class="{
        'h-[74px] border-black/10': currentView !== 'conversation-messages',
        'border-transparent before:opacity-100': currentView === 'conversation-messages'
    }"
>
    <div
        class="col-start-1 col-end-1 row-start-1 row-end-1 w-full"
        x-show="currentView !== 'conversation-messages'"
        x-transition
    >
        @include('chatbot::frontend-ui.components.app-shell-navigation')
    </div>

    <div
        class="pointer-events-none absolute bottom-full end-0 start-[15%] mb-2 flex flex-row-reverse flex-wrap gap-2"
        x-cloak
        x-show="currentView === 'conversation-messages' && suggestedPrompts.length && !messages.find(m => m.role === 'user')"
        x-transition
        x-ref="suggestedPromptsContainer"
    >
        <template
            x-for="(prompt, index) in suggestedPrompts"
            :key="'floating-bar-suggested-prompt-' + index"
        >
            <button
                class="pointer-events-auto relative z-1 overflow-hidden rounded-xl px-5 py-3 text-xs text-[--lqd-ext-chat-primary] backdrop-blur-lg transition before:absolute before:inset-0 before:-z-1 before:bg-[--lqd-ext-chat-primary] before:opacity-10 hover:bg-[--lqd-ext-chat-primary] hover:text-[--lqd-ext-chat-primary-foreground]"
                type="button"
                @click="applySuggestedPrompt(prompt)"
                x-text="getSuggestedPromptLabel(prompt, index)"
            >
            </button>
        </template>
    </div>

    <div
        class="col-start-1 col-end-1 row-start-1 row-end-1 flex"
        x-cloak
        x-show="currentView === 'conversation-messages'"
        x-transition
    >
        @include('chatbot::frontend-ui.components.conversation-form')
    </div>
</div>
