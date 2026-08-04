<div id="chatbot-team-chat-panel" class="hidden h-[calc(100vh-49px)]" data-team-chat-panel data-chatbot-workspace="team">
    <div class="flex items-center justify-between border-b p-4">
        <div>
            <h2 class="font-semibold">{{ __('Team conversations') }}</h2>
            <p class="text-xs opacity-70">{{ __('Direct messages and group chats share the chatbot inbox.') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <input type="search" class="form-control form-control-sm" data-team-chat-local-search placeholder="{{ __('Search conversations and messages') }}">
            <span class="text-xs opacity-60" data-team-chat-stale hidden>{{ __('Offline data') }}</span>
            <button type="button" data-team-chat-new class="lqd-btn lqd-btn-primary">{{ __('New group') }}</button>
        </div>
    </div>
    <div class="grid min-h-[32rem] grid-cols-12">
        <aside class="col-span-4 overflow-y-auto border-r" data-team-chat-list></aside>
        <section class="col-span-8" data-team-chat-thread>
            <div class="p-6 text-sm opacity-70">{{ __('Select a team conversation.') }}</div>
        </section>
    </div>
</div>

@include('chatbot::staff-inbox.team-chat.channels')

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" data-team-group-modal>
    <form class="w-full max-w-lg rounded-xl bg-background p-5 shadow-xl" data-team-group-form>
        <div class="mb-4 flex items-center justify-between"><h3 class="font-semibold">{{ __('Create team conversation') }}</h3><button type="button" data-team-modal-close aria-label="{{ __('Close') }}">×</button></div>
        <label class="mb-3 block"><span class="mb-1 block text-sm">{{ __('Conversation type') }}</span><select class="form-control" name="type"><option value="group">{{ __('Group') }}</option><option value="direct">{{ __('Direct message') }}</option></select></label>
        <label class="mb-3 block" data-team-group-name-field><span class="mb-1 block text-sm">{{ __('Group name') }}</span><input class="form-control" name="name" maxlength="255"></label>
        <label class="mb-3 block"><span class="mb-1 block text-sm">{{ __('Member user IDs') }}</span><input class="form-control" name="user_ids" required placeholder="12, 18, 24"><small class="opacity-60">{{ __('Enter one or more existing user IDs separated by commas.') }}</small></label>
        <p class="mb-3 hidden rounded bg-red-500/10 p-2 text-sm text-red-600" data-team-group-error></p>
        <div class="flex justify-end gap-2"><button type="button" class="lqd-btn lqd-btn-ghost" data-team-modal-close>{{ __('Cancel') }}</button><button type="submit" class="lqd-btn lqd-btn-primary">{{ __('Create') }}</button></div>
    </form>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" data-team-channel-modal>
    <form class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-xl bg-background p-5 shadow-xl" data-team-channel-form>
        <div class="mb-4 flex items-center justify-between"><h3 class="font-semibold">{{ __('Create channel') }}</h3><button type="button" data-team-modal-close aria-label="{{ __('Close') }}">×</button></div>
        <div class="grid grid-cols-2 gap-3">
            <label class="col-span-2 block"><span class="mb-1 block text-sm">{{ __('Name') }}</span><input class="form-control" name="name" required maxlength="255"></label>
            <label class="col-span-2 block"><span class="mb-1 block text-sm">{{ __('Description') }}</span><textarea class="form-control" name="description" maxlength="2000"></textarea></label>
            <label class="block"><span class="mb-1 block text-sm">{{ __('Visibility') }}</span><select class="form-control" name="visibility"><option value="public">{{ __('Public') }}</option><option value="private">{{ __('Private') }}</option></select></label>
            <label class="block"><span class="mb-1 block text-sm">{{ __('Posting policy') }}</span><select class="form-control" name="posting_policy"><option value="members">{{ __('All members') }}</option><option value="moderators">{{ __('Moderators and owners') }}</option><option value="owners">{{ __('Owners only') }}</option></select></label>
            <label class="block"><span class="mb-1 block text-sm">{{ __('Category') }}</span><input class="form-control" name="category" maxlength="120"></label>
            <label class="block"><span class="mb-1 block text-sm">{{ __('Member user IDs') }}</span><input class="form-control" name="user_ids" placeholder="12, 18"></label>
            <label class="block"><span class="mb-1 block text-sm">{{ __('Linked entity type') }}</span><input class="form-control" name="linked_entity_type" placeholder="job, customer, project"></label>
            <label class="block"><span class="mb-1 block text-sm">{{ __('Linked entity ID') }}</span><input class="form-control" name="linked_entity_id"></label>
            <label class="col-span-2 flex items-center gap-2"><input type="checkbox" name="is_announcement" value="1">{{ __('Announcement-only channel') }}</label>
        </div>
        <p class="my-3 hidden rounded bg-red-500/10 p-2 text-sm text-red-600" data-team-channel-error></p>
        <div class="flex justify-end gap-2"><button type="button" class="lqd-btn lqd-btn-ghost" data-team-modal-close>{{ __('Cancel') }}</button><button type="submit" class="lqd-btn lqd-btn-primary">{{ __('Create channel') }}</button></div>
    </form>
</div>

<script src="{{ asset('vendor/chatbot/js/team-chat/team-chat-client.js') }}" defer></script>
<script src="{{ asset('vendor/chatbot/js/team-chat/business-channels.js') }}" defer></script>
<script src="{{ asset('chatbot-pwa/team-chat-local-first.js') }}" defer></script>
<script src="{{ asset('chatbot-pwa/business-channels-local-first.js') }}" defer></script>
<script src="{{ asset('chatbot-pwa/team-chat-local-ui.js') }}" defer></script>
