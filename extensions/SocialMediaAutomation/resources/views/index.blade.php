@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('social-media-automation::t.automation'))
@section('titlebar_subtitle', __('Set up personalized automation triggers for your social media channels.'))
@section('titlebar_pretitle', '')

@section('titlebar_actions')
    <x-button
        variant="primary"
        href="{{ route('dashboard.user.social-media.automation.create') }}"
    >
        <x-tabler-plus class="size-4" />
        {{ __('social-media-automation::t.create_automation') }}
    </x-button>
@endsection

@section('content')
    <div
        class="py-10"
        x-data="automationList"
    >
        @if (isset($planAllows) && !$planAllows)
            <div class="text-center">
                <x-empty-state
                    class="mb-4"
                    icon="tabler-lock"
                    :title="__('Upgrade Your Plan')"
                    :description="__('Comment & DM Automation is not available in your current plan. Upgrade to unlock automated comment replies and DM sequences.')"
                />
                <x-button href="{{ route('dashboard.user.payment.subscription') }}">
                    <x-tabler-crown class="size-4" />
                    {{ __('Upgrade Plan') }}
                </x-button>
            </div>
        @else
            @if ($automations->isEmpty())
                <x-empty-state
                    icon="tabler-settings-off"
                    :title="__('social-media-automation::t.no_automations')"
                    :description="__('social-media-automation::t.no_automations_desc')"
                />
            @else
                <div class="mb-9">
                    <div class="relative">
                        <x-tabler-search class="absolute start-4 top-1/2 size-4 -translate-y-1/2" />
                        <x-forms.input
                            class="rounded-full bg-foreground/10 ps-11 placeholder:text-foreground"
                            size="lg"
                            type="search"
                            placeholder="{{ __('Search') }}"
                            x-model="search"
                        />
                    </div>
                </div>

                <div x-show="!search || hasResults">
                    <x-table>
                        <x-slot:head>
                            <tr>
                                <th>
                                    {{ __('Name') }}
                                </th>
                                <th>
                                    {{ __('Status') }}
                                </th>
                                <th>
                                    {{ __('Created At') }}
                                </th>
                                <th>
                                    {{ __('Account') }}
                                </th>
                                <th class="text-end">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </x-slot:head>

                        <x-slot:body>
                            @foreach ($automations as $automation)
                                @php
                                    $item = is_array($automation) ? (object) $automation : $automation;
                                    $name = $item->name;
                                    $status = is_array($automation) ? $item->status : $item->status;
                                    $createdAt = is_array($automation) ? $item->created_at : $item->created_at->format('M j, Y');
                                    $platform = is_array($automation) ? $item->platform : $item->platform;
                                @endphp
                                <tr x-show="!search || '{{ strtolower($name) }}'.includes(search.toLowerCase())">
                                    <td class="font-medium">
                                        {{ $name }}
                                    </td>
                                    <td>
                                        <span @class([
                                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium leading-none',
                                            'bg-green-500/10 text-green-600' => $status === 'live',
                                            'bg-foreground/5 text-foreground' => $status === 'draft',
                                            'bg-yellow-500/10 text-yellow-600' => $status === 'paused',
                                            'bg-red-500/10 text-red-600' => $status === 'expired',
                                        ])>
                                            @if ($status === 'live')
                                                <span class="size-1.5 rounded-full bg-green-500"></span>
                                            @elseif ($status === 'expired')
                                                <span class="size-1.5 rounded-full bg-red-500"></span>
                                            @endif
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td class="text-foreground/70">
                                        {{ is_string($createdAt) ? $createdAt : $createdAt }}
                                    </td>
                                    <td>
                                        @php
                                            $platformName = is_array($automation) ? $item->platform_name ?? '' : $item->platform->platform ?? '';
                                            $accountName = is_array($automation)
                                                ? $item->account_name ?? ''
                                                : (is_callable([$platform, 'username'])
                                                    ? $platform->username()
                                                    : (is_object($platform) && isset($platform->username)
                                                        ? (is_callable($platform->username)
                                                            ? ($platform->username)()
                                                            : $platform->username)
                                                        : ''));
                                        @endphp
                                        <div class="flex items-center gap-2">
                                            @php
                                                $iconName = $platformName === 'twitter' ? 'x' : $platformName;
                                                $iconPath = 'vendor/social-media/icons/' . $iconName . '.svg';
                                            @endphp
                                            @if (in_array($platformName, ['instagram', 'facebook', 'tiktok', 'linkedin', 'x', 'twitter']))
                                                <img
                                                    class="size-6"
                                                    src="{{ asset($iconPath) }}"
                                                    alt="{{ $platformName }}"
                                                >
                                            @else
                                                <span class="grid size-6 place-items-center rounded-full bg-foreground/10">
                                                    <x-tabler-social class="size-3.5 text-foreground/50" />
                                                </span>
                                            @endif
                                            <span class="text-sm">{{ $accountName }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1">
                                            @if (!is_array($automation))
                                                <x-button
                                                    class="size-9"
                                                    size="none"
                                                    variant="ghost-shadow"
                                                    hover-variant="primary"
                                                    href="{{ route('dashboard.user.social-media.automation.edit', $item->id) }}"
                                                    title="{{ __('Edit') }}"
                                                >
                                                    <x-tabler-pencil class="size-4" />
                                                </x-button>
                                            @else
                                                <x-button
                                                    class="size-9"
                                                    size="none"
                                                    variant="ghost-shadow"
                                                    hover-variant="primary"
                                                    title="{{ __('Edit') }}"
                                                >
                                                    <x-tabler-pencil class="size-4" />
                                                </x-button>
                                            @endif

                                            <div
                                                class="relative"
                                                x-data="{ open: false }"
                                                @click.outside="open = false"
                                            >
                                                <button
                                                    class="inline-flex size-9 items-center justify-center rounded-button text-foreground/70 transition-colors hover:bg-foreground/5 hover:text-foreground"
                                                    type="button"
                                                    x-ref="moreBtn"
                                                    @click="open = !open"
                                                >
                                                    <x-tabler-dots-vertical class="size-4" />
                                                </button>
                                                <template x-teleport="body">
                                                    <div
                                                        class="fixed z-[9999] w-40 overflow-hidden rounded-dropdown border border-dropdown-border bg-dropdown-background shadow-lg shadow-black/5"
                                                        x-show="open"
                                                        x-transition
                                                        x-cloak
                                                        x-anchor.bottom-end.offset.4="$refs.moreBtn"
                                                    >
                                                        @if (!is_array($automation))
                                                            <button
                                                                class="flex w-full items-center gap-2 px-3 py-2 text-start text-xs hover:bg-foreground/5"
                                                                @click="toggleStatus({{ $item->id }}); open = false"
                                                            >
                                                                <x-tabler-player-play
                                                                    class="size-4"
                                                                    x-show="'{{ $status }}' !== 'live'"
                                                                />
                                                                <x-tabler-player-pause
                                                                    class="size-4"
                                                                    x-show="'{{ $status }}' === 'live'"
                                                                />
                                                                {{ $status === 'live' ? __('Pause') : __('Set Live') }}
                                                            </button>
                                                            <button
                                                                class="flex w-full items-center gap-2 px-3 py-2 text-start text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10"
                                                                @click="deleteAutomation({{ $item->id }}); open = false"
                                                            >
                                                                <x-tabler-trash class="size-4" />
                                                                {{ __('Delete') }}
                                                            </button>
                                                        @else
                                                            <button
                                                                class="flex w-full items-center gap-2 px-3 py-2 text-start text-xs hover:bg-foreground/5"
                                                                @click="toastr.error('{{ trans('This feature is disabled in demo mode.') }}'); open = false"
                                                            >
                                                                <x-tabler-player-play
                                                                    class="size-4"
                                                                    x-show="'{{ $status }}' !== 'live'"
                                                                />
                                                                <x-tabler-player-pause
                                                                    class="size-4"
                                                                    x-show="'{{ $status }}' === 'live'"
                                                                />
                                                                {{ $status === 'live' ? __('Pause') : __('Set Live') }}
                                                            </button>
                                                        @endif
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </x-slot:body>
                    </x-table>
                </div>

                <x-empty-state
                    icon="tabler-search-off"
                    :title="__('No matching automations')"
                    :description="__('Try a different search term.')"
                    x-show="search && !hasResults"
                    x-cloak
                />
            @endif
        @endif
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('automationList', () => ({
                search: '',
                automationNames: @json($automations->map(fn($a) => strtolower(is_array($a) ? $a['name'] : $a->name))->values()),

                get hasResults() {
                    if (!this.search) return true;
                    const term = this.search.toLowerCase();
                    return this.automationNames.some(name => name.includes(term));
                },

                async toggleStatus(id) {
                    try {
                        const response = await fetch(`/dashboard/user/social-media/automation/${id}/status`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                        });
                        const data = await response.json();
                        if (data.status === 'success') {
                            window.location.reload();
                        } else {
                            toastr.error(data.message);
                        }
                    } catch (e) {
                        toastr.error('{{ trans('An error occurred.') }}');
                    }
                },

                async deleteAutomation(id) {
                    if (!confirm('{{ trans('Are you sure you want to delete this automation?') }}')) {
                        return;
                    }
                    try {
                        const response = await fetch(`/dashboard/user/social-media/automation/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                        });
                        const data = await response.json();
                        if (data.status === 'success') {
                            window.location.reload();
                        } else {
                            toastr.error(data.message);
                        }
                    } catch (e) {
                        toastr.error('{{ trans('An error occurred.') }}');
                    }
                },
            }));
        });
    </script>
@endpush
