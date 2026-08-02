@extends('panel.layout.app', [
    'disable_tblr' => true,
    'disable_header' => true,
    'disable_footer' => true,
    'disable_titlebar' => true,
    'disable_navbar' => true,
    'layout_wide' => true,
    'disable_mobile_bottom_menu' => true,
])
@section('title', $automation ? __('social-media-automation::t.edit_automation') : __('social-media-automation::t.create_automation'))

@push('before-head-close')
    <style>
        :root {
            --header-height: 58px;
        }

        .lqd-header {
            display: none !important;
        }

        .focus-mode .lqd-page-content-container {
            max-width: 100% !important;
        }
    </style>
@endpush

@push('after-body-open')
    <script>
        (() => {
            document.body.classList.add('navbar-shrinked');
        })();
    </script>
@endpush

@section('content')
    <script>
        window.__automationData = {
            automation: @json($automation),
            accounts: @json($accounts),
            permissionInfo: @json($permissionInfo ?? []),
            routes: {
                index: '{{ route('dashboard.user.social-media.automation.index') }}',
                posts: '{{ route('dashboard.user.social-media.automation.posts') }}',
                uploadImage: '{{ route('dashboard.user.social-media.automation.upload-image') }}',
            },
            csrfToken: '{{ csrf_token() }}',
            translations: @json(__('social-media-automation::t')),
        };
    </script>
    <div
        class="h-screen"
        x-data="automationBuilder"
    >
        {{-- Account Selection Step --}}
        <div
            class="min-h-screen w-full overflow-y-auto"
            @if ($automation) x-cloak @endif
            x-show="!showCanvas"
        >
            @include('social-media-automation::builder.partials.account-selection')
        </div>

        {{-- React Flow Canvas --}}
        <template x-if="showCanvas">
            <div class="h-screen w-full overflow-hidden">
                <div
                    class="size-full"
                    id="automation-builder-root"
                    x-init="$nextTick(() => {
                        const tryMount = () => window.__mountAutomationBuilder ? window.__mountAutomationBuilder() : setTimeout(tryMount, 50);
                        tryMount();
                    })"
                ></div>
            </div>
        </template>
    </div>
@endsection

@push('script')
    @viteReactRefresh
    @vite('app/Extensions/SocialMediaAutomation/resources/assets/js/automation-builder.jsx')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('automationBuilder', () => ({
                selectedAccountId: window.__automationData.automation?.social_media_platform_id ?? null,
                accounts: window.__automationData.accounts ?? [],
                permissionInfo: window.__automationData.permissionInfo ?? {},
                showCanvas: !!window.__automationData.automation,

                get selectedAccountPermission() {
                    return this.permissionInfo[this.selectedAccountId] ?? null;
                },
                get hasPermissions() {
                    const info = this.selectedAccountPermission;
                    return info ? info.has_permissions : true;
                },
                get missingScopes() {
                    const info = this.selectedAccountPermission;
                    return info?.missing_scopes ?? [];
                },
                get platformNotes() {
                    const info = this.selectedAccountPermission;
                    return info?.notes ?? '';
                },
                get platformRateLimit() {
                    const info = this.selectedAccountPermission;
                    return info?.rate_limit ?? '';
                },
                selectAccount() {
                    if (!this.selectedAccountId) return;
                    window.__automationData.selectedAccountId = this.selectedAccountId;
                    this.showCanvas = true;
                },
            }));
        });
    </script>
@endpush
